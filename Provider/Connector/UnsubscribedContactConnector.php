<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

/**
 * Contacts unsubscribed from Address book Connector
 */
class UnsubscribedContactConnector extends AbstractDotmailerConnector
{
    public const TYPE = 'unsubscribed_contact';
    public const IMPORT_JOB = 'dotmailer_unsubscribed_contact_import';

    #[\Override]
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Unsubscribed from Address Book Contacts.');
        $addressBooks = $this->managerRegistry->getRepository(AddressBook::class)
            ->getAddressBooksToSync($this->getChannel());

        return $this->transport->getUnsubscribedContacts($addressBooks);
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.unsubscribed_contact.label';
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }
}
