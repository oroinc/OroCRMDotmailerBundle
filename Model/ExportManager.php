<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
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
        $exportRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        $this->dotmailerTransport->init($channel->getTransport());

        $isExportFinished = true;

        foreach ($exportRepository->getNotFinishedExports($channel) as $export) {
            $dotmailerStatus = $this->dotmailerTransport->getImportStatus($export->getImportId());
            $exportStatus = $exportRepository->getStatus($dotmailerStatus->status);

            $export->setStatus($exportStatus);
            if ($exportRepository->isNotFinishedStatus($exportStatus)) {
                $isExportFinished = false;
            }
        }

        $this->managerRegistry->getManager()->flush();

        if ($isExportFinished) {
            $this->processExportFaults($channel);
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
        $exportRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        $addressBooks = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findBy(['channel' => $channel]);

        $lastFinishedStatus = null;
        foreach ($addressBooks as $addressBook) {
            $addressBookExports = $exportRepository->getExportsByAddressBook($addressBook);

            $isExportFinished = true;
            $lastErrorStatus = null;
            foreach ($addressBookExports as $addressBookExport) {
                $status = $addressBookExport->getStatus();

                if ($exportRepository->isErrorStatus($status) && !$lastErrorStatus) {
                    $lastErrorStatus = $status;
                }

                $isExportFinished = $isExportFinished && $exportRepository->isFinishedStatus($status);
            }

            if ($isExportFinished) {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getFinishedStatus(), true);
            } elseif ($lastErrorStatus) {
                $this->updateAddressBookSyncStatus($addressBook, $lastErrorStatus, true);
            } else {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getNotFinishedStatus(), false);
            }
        }
    }

    /**
     * @param AddressBook       $addressBook
     * @param AbstractEnumValue $status
     * @param bool              $updateLastExportedAt
     */
    protected function updateAddressBookSyncStatus(
        AddressBook $addressBook,
        AbstractEnumValue $status,
        $updateLastExportedAt
    ) {
        $addressBook->setSyncStatus($status);
        if ($updateLastExportedAt) {
            $addressBook->setLastExportedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
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
