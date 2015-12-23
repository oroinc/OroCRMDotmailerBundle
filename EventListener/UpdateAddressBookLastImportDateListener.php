<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class UpdateAddressBookLastImportDateListener implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param SyncEvent $syncEvent
     */
    public function afterSyncFinished(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent)) {
            return;
        }

        $jobResult = $syncEvent->getJobResult();
        $addressBookIds = $jobResult->getContext()->getValue(ContactConnector::PROCESSED_ADDRESS_BOOK_IDS);

        $connectorData = $jobResult->getContext()->getValue(ContactConnector::CONTEXT_CONNECTOR_DATA_KEY);
        if (empty($connectorData[ContactConnector::LAST_SYNC_DATE_KEY])) {
            throw new RuntimeException(
                sprintf('Connector context value "%s" not found.', ContactConnector::LAST_SYNC_DATE_KEY)
            );
        }
        $contactConnectorLastSyncDate = new \DateTime(
            $connectorData[ContactConnector::LAST_SYNC_DATE_KEY],
            new \DateTimeZone('UTC')
        );

        $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->bulkUpdateLastImportedAt($contactConnectorLastSyncDate, $addressBookIds);
    }

    /**
     * @param SyncEvent $syncEvent
     *
     * @return bool
     */
    protected function isApplicable(SyncEvent $syncEvent)
    {
        return $syncEvent->getJobName() == ContactConnector::IMPORT_JOB
            && $syncEvent->getJobResult()->isSuccessful();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SyncEvent::SYNC_AFTER => 'afterSyncFinished'
        );
    }
}
