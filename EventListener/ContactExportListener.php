<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class ContactExportListener extends AbstractImportExportListener
{
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

    /**
     * @param SyncEvent $syncEvent
     */
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
        $this->registry->getRepository('OroCRMDotmailerBundle:Contact')
            ->bulkRemoveNotExportedContacts($channel);

        /** @var AbstractEnumValue $inProgressStatus */
        $inProgressStatus = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
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
            ->getRepository('OroCRMDotmailerBundle:AddressBook');

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
