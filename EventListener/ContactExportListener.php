<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class ContactExportListener implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ExportManager
     */
    protected $exportManager;

    /**
     * @param ManagerRegistry $registry
     * @param ExportManager   $exportManager
     */
    public function __construct(ManagerRegistry $registry, ExportManager $exportManager)
    {
        $this->registry = $registry;
        $this->exportManager = $exportManager;
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

    /**
     * @param SyncEvent $syncEvent
     */
    public function beforeSyncStarted(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent)) {
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
            ->getRepository('OroDotmailerBundle:AddressBookContactsExport')
            ->getNotFinishedStatus();
        $addressBooks = $this->getAddressBooksToSync($channel, $configuration);
        foreach ($addressBooks as $addressBook) {
            $addressBook->setSyncStatus($inProgressStatus);
        }
    }

    /**
     * @param SyncEvent $syncEvent
     */
    public function afterSyncFinished(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent)) {
            return;
        }

        $configuration = $syncEvent->getConfiguration();
        $channel= $this->getChannel($configuration);
        $this->exportManager->updateAddressBooksSyncStatus($channel);
    }

    /**
     * @param SyncEvent $syncEvent
     *
     * @return bool
     */
    protected function isApplicable(SyncEvent $syncEvent)
    {
        return $syncEvent->getJobName() == ExportContactConnector::EXPORT_JOB;
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

    /**
     * @param array $configuration
     *
     * @return Channel
     */
    protected function getChannel(array $configuration)
    {
        if (empty($configuration['import']['channel'])) {
            throw new RuntimeException('Integration channel Id required');
        }
        $channel = $this->registry
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($configuration['import']['channel']);
        if (!$channel) {
            throw new RuntimeException('Integration channel is not exist');
        }

        return $channel;
    }
}
