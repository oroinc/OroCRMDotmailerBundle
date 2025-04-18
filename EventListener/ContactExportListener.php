<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

/**
 * Listener for updating address books sync status on sync started and finished
 */
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

    #[\Override]
    public static function getSubscribedEvents(): array
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
        $this->registry->getRepository(Contact::class)
            ->bulkRemoveNotExportedContacts($channel);

        /** @var EnumOptionInterface $inProgressStatus */
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
        $channel = $this->getChannel($configuration);
        $this->exportManager->updateAddressBooksSyncStatus($channel);
    }

    #[\Override]
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
            ->getRepository(AddressBook::class);

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
