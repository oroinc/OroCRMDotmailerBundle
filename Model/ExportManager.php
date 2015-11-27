<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
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
     * @var SyncProcessor
     */
    protected $syncProcessor;

    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $addressBookContact;

    /**
     * @param ManagerRegistry    $managerRegistry
     * @param DotmailerTransport $dotmailerTransport
     * @param SyncProcessor      $syncProcessor
     * @param Executor           $executor
     * @param string             $addressBookContact
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        DotmailerTransport $dotmailerTransport,
        SyncProcessor $syncProcessor,
        Executor $executor,
        $addressBookContact
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dotmailerTransport = $dotmailerTransport;
        $this->syncProcessor = $syncProcessor;
        $this->executor = $executor;
        $this->addressBookContact = $addressBookContact;
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

        /**
         * @var EntityRepository $addressBookContactRepository
         */
        $addressBookContactRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact');
        $isExportFinished = true;

        $importStatuses = $addressBookContactsExportRepository->getNotFinishedExports($channel);
        foreach ($importStatuses as $importStatus) {
            $apiImportStatus = $this->dotmailerTransport->getImportStatus($importStatus->getImportId());
            if (!$status = $statusRepository->find($apiImportStatus->status)) {
                throw new RuntimeException('Status is not exist');
            }
            if ($apiImportStatus->status == AddressBookContactsExport::STATUS_NOT_FINISHED) {
                $isExportFinished = false;
            }
            $importStatus->setStatus($status);
        }

        if ($isExportFinished) {
            $jobResult = $this->startUpdateSkippedContactsStatusJob($channel);
            if (!$jobResult) {
                throw new RuntimeException('Update skipped contacts failed.');
            }
            if (!$this->isImportAlreadyStarted($channel)) {
                $importJobResult = $this->startImportContactsJob($channel);
                if (!$importJobResult) {
                    throw new RuntimeException('Import exported data failed.');
                }
            }

            $addressBookContactRepository->createQueryBuilder('addressBookContact')
                ->update()
                ->where('addressBookContact.channel =:channel')
                ->set('addressBookContact.scheduledForExport', ':scheduledForExport')
                ->getQuery()
                ->execute(['channel' => $channel, 'scheduledForExport' => false]);

            $this->updateAddressBookStatus($channel);
        }

        $this->managerRegistry->getManager()->flush();

        return $isExportFinished;
    }

    protected function startUpdateSkippedContactsStatusJob(Channel $channel)
    {
        $configuration = [
            ProcessorRegistry::TYPE_IMPORT => [
                'entityName'     => $this->addressBookContact,
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

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    protected function startImportContactsJob(Channel $channel)
    {
        return $this->syncProcessor->process(
            $channel,
            ContactConnector::TYPE,
            []
        );
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
     */
    protected function updateAddressBookStatus(Channel $channel)
    {
        $addressBookRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBook');
        $className = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->managerRegistry
            ->getRepository($className);
        $addressBookContactsExportRepository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        $finishStatus = $statusRepository->find(AddressBookContactsExport::STATUS_FINISH);
        $addressBooks = $addressBookRepository->findBy(['channel' => $channel]);
        $lastSyncDate = new \DateTime('now', new \DateTimeZone('UTC'));
        foreach ($addressBooks as $addressBook) {
            $failedExport = $addressBookContactsExportRepository->getLastFailedExport($addressBook);

            if ($failedExport) {
                $addressBook->setSyncStatus($failedExport->getStatus());
            } else {
                $addressBook->setSyncStatus($finishStatus);
            }
            $addressBook->setLastExportedAt($lastSyncDate);
        }
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    protected function isImportAlreadyStarted(Channel $channel)
    {
        $running = $this->managerRegistry->getRepository('OroIntegrationBundle:Channel')
            ->getRunningSyncJobsCount(SyncCommand::COMMAND_NAME, $channel->getId());

        return $running > 0;
    }
}
