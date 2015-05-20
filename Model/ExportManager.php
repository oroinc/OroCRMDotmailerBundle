<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
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
     */
    public function updateExportResults(Channel $channel)
    {
        $repository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');
        $enumClassName = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->managerRegistry
            ->getRepository($enumClassName);
        $this->dotmailerTransport->init($channel->getTransport());

        $isExportFinished = true;

        $importStatuses = $repository->getNotFinishedImports($channel);
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

            /**
             * @var EntityRepository $entityRepository
             */
            $entityRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact');
            $entityRepository->createQueryBuilder('addressBookContact')
                ->update()
                ->where('addressBookContact.channel =:channel')
                ->set('addressBookContact.scheduledForExport', ':scheduledForExport')
                ->getQuery()
                ->execute(['channel' => $channel, 'scheduledForExport' => false]);
        }
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    protected function startImportContactsJob(Channel $channel)
    {
        return $this->syncProcessor->process($channel, ContactConnector::TYPE);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        $repository = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');
        return $repository->isExportFinished($channel);
    }
}
