<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy\ContactStrategy;
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
     * @param ManagerRegistry    $managerRegistry
     * @param DotmailerTransport $dotmailerTransport
     * @param SyncProcessor      $syncProcessor
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        DotmailerTransport $dotmailerTransport,
        SyncProcessor $syncProcessor
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dotmailerTransport = $dotmailerTransport;
        $this->syncProcessor = $syncProcessor;
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
            $importJobResult = $this->startImportContactsJob($channel);
            if (!$importJobResult) {
                throw new RuntimeException('Import exported data failed.');
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
            [ContactStrategy::FIND_CONTACT_BY_EMAIL_OPTION => true]
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
            $addressBook->setLastSynced($lastSyncDate);
        }
    }
}
