<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

/**
 * Listener for updating address books last imported date on sync finished
 */
class UpdateAddressBookLastImportDateListener extends AbstractImportExportListener
{
    public function afterSyncFinished(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent, ContactConnector::IMPORT_JOB)) {
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
            ->getRepository(AddressBook::class)
            ->bulkUpdateLastImportedAt($contactConnectorLastSyncDate, $addressBookIds);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(
            SyncEvent::SYNC_AFTER => 'afterSyncFinished'
        );
    }
}
