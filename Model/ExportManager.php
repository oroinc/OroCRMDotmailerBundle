<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class ExportManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var DotmailerTransport
     */
    protected $dotmailerTransport;

    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $addressBookContactClassName;

    /**
     * @param ManagerRegistry    $managerRegistry
     * @param DotmailerTransport $dotmailerTransport
     * @param Executor           $executor
     * @param string             $addressBookContactClassName
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        DotmailerTransport $dotmailerTransport,
        Executor $executor,
        $addressBookContactClassName
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dotmailerTransport = $dotmailerTransport;
        $this->executor = $executor;
        $this->addressBookContactClassName = $addressBookContactClassName;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        return $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->isExportFinished($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFaultsProcessed(Channel $channel)
    {
        return $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->isExportFaultsProcessed($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function updateExportResults(Channel $channel)
    {
        $addressBookContactsExportRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        $className = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->managerRegistry
            ->getRepository($className);
        $this->dotmailerTransport->init($channel->getTransport());

        $isExportFinished = true;

        $importStatuses = $addressBookContactsExportRepository->getNotFinishedExports($channel);
        foreach ($importStatuses as $importStatus) {
            $apiImportStatus = $this->dotmailerTransport->getImportStatus($importStatus->getImportId());
            /** @var AbstractEnumValue|null $status */
            if (!$status = $statusRepository->find($apiImportStatus->status)) {
                throw new RuntimeException('Status is not exist');
            }
            if ($apiImportStatus->status == AddressBookContactsExport::STATUS_NOT_FINISHED) {
                $isExportFinished = false;
            }
            $importStatus->setStatus($status);
        }

        if ($isExportFinished) {
            $this->processExportFaults($channel);
        } else {
            $this->managerRegistry->getManager()->flush();
        }

        return $isExportFinished;
    }

    /**
     * @param Channel $channel
     */
    public function processExportFaults(Channel $channel)
    {
        $jobResult = $this->startUpdateSkippedContactsStatusJob($channel);
        if (!$jobResult) {
            throw new RuntimeException('Update skipped contacts failed.');
        }

        $this->updateAddressBooksSyncStatus($channel);

        $this->managerRegistry->getManager()->flush();
    }

    /**
     * @param Channel $channel
     */
    public function updateAddressBooksSyncStatus(Channel $channel)
    {
        $statusRepository = $this->managerRegistry
            ->getRepository(ExtendHelper::buildEnumValueClassName('dm_import_status'));
        $addressBookContactsExportRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        /** @var AbstractEnumValue $finishStatus */
        $finishStatus = $statusRepository->find(AddressBookContactsExport::STATUS_FINISH);
        /** @var AbstractEnumValue $inProgressStatus */
        $inProgressStatus = $statusRepository->find(AddressBookContactsExport::STATUS_NOT_FINISHED);

        $addressBooks = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findBy(['channel' => $channel]);
        foreach ($addressBooks as $addressBook) {
            $exports = $addressBookContactsExportRepository->getExportResults($addressBook);

            $isExportFinished = true;
            foreach ($exports as $export) {
                if ($export->getStatus() != AddressBookContactsExport::STATUS_FINISH) {
                    $isExportFinished = false;
                }
            }
            if ($isExportFinished) {
                $this->updateAddressBookSyncStatus($addressBook, $finishStatus);
            } else {
                if ($failedExport = $this->getLastFailedExport($exports)) {
                    $this->updateAddressBookSyncStatus($addressBook, $failedExport->getStatus());
                } else {
                    $addressBook->setSyncStatus($inProgressStatus);
                }
            }
        }
    }

    /**
     * @param AddressBookContactsExport[] $exports
     *
     * @return AddressBookContactsExport|null
     */
    protected function getLastFailedExport(array $exports)
    {
        foreach ($exports as $export) {
            if ($export->getStatus() != AddressBookContactsExport::STATUS_NOT_FINISHED) {
                return $export;
            }
        }

        return null;
    }

    /**
     * @param AddressBook       $addressBook
     * @param AbstractEnumValue $status
     */
    protected function updateAddressBookSyncStatus(AddressBook $addressBook, AbstractEnumValue $status)
    {
        $addressBook->setSyncStatus($status);
        $addressBook->setLastSynced(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @param Channel $channel
     *
     * @return JobResult
     */
    protected function startUpdateSkippedContactsStatusJob(Channel $channel)
    {
        $configuration = [
            ProcessorRegistry::TYPE_IMPORT => [
                'entityName'     => $this->addressBookContactClassName,
                'channel'        => $channel->getId(),
                'channelType'    => $channel->getType(),
            ],
        ];

        return $this->executor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            'dotmailer_import_not_exported_contact',
            $configuration
        );
    }
}
