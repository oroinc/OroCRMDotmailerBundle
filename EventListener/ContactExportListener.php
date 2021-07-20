<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class ContactExportListener extends AbstractImportExportListener
{
    /**
     * @var QueueExportManager
     */
    protected $exportManager;

    public function __construct(ManagerRegistry $registry, QueueExportManager $exportManager)
    {
        $this->exportManager = $exportManager;
        parent::__construct($registry);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SyncEvent::SYNC_BEFORE => 'beforeSyncStarted',
            SyncEvent::SYNC_AFTER => 'afterSyncFinished'
        );
    }

    public function beforeSyncStarted(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent, ExportContactConnector::EXPORT_JOB)) {
            return;
        }

        $configuration = $syncEvent->getConfiguration();

        $channel = $this->getChannel($configuration);

        /**
         * Remove contact drafts which was not fully exported to Dotmailer
         */
        $this->registry->getRepository('OroDotmailerBundle:Contact')
            ->bulkRemoveNotExportedContacts($channel);

        /** @var AbstractEnumValue $inProgressStatus */
        $inProgressStatus = $this->registry
            ->getRepository(AddressBookContactsExport::class)
            ->getNotFinishedStatus();
        $addressBooks = $this->getAddressBooksToSync($channel, $configuration);
        foreach ($addressBooks as $addressBook) {
            $addressBook->setSyncStatus($inProgressStatus);
        }
    }

    public function afterSyncFinished(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent, ExportContactConnector::EXPORT_JOB)) {
            return;
        }

        $configuration = $syncEvent->getConfiguration();
        $channel= $this->getChannel($configuration);
        $this->exportManager->updateAddressBooksSyncStatus($channel);
    }

    /**
     * @inheritdoc
     */
    protected function isApplicable(SyncEvent $syncEvent, $job)
    {
        return $syncEvent->getJobName() == $job;
    }

    /**
     * @param Channel $channel
     *
     * @param array   $configuration
     *
     * @return AddressBook[]
     */
    protected function getAddressBooksToSync(Channel $channel, array $configuration)
    {
        $repository = $this->registry
            ->getRepository('OroDotmailerBundle:AddressBook');

        if (!empty($configuration['import'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION])) {
            $addressBook = $repository->find($configuration['import']['address-book']);
            if (!$addressBook) {
                throw new RuntimeException(
                    sprintf(
                        'Address book \'%s\' not found.',
                        $configuration['import'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION]
                    )
                );
            }
        }

        return $repository->getAddressBooksToSync($channel);
    }
}
