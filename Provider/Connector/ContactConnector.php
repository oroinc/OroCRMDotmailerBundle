<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

/**
 * Contact Connector
 */
class ContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'contact';
    const IMPORT_JOB = 'dotmailer_new_contacts';
    const PROCESSED_ADDRESS_BOOK_IDS = 'processed_address_book_ids';

    #[\Override]
    protected function getConnectorSource()
    {
        $addressBooksToSynchronize = $this->managerRegistry
            ->getRepository(AddressBook::class)
            ->getAddressBooksToSync($this->getChannel());

        $this->getContext()
            ->setValue(self::PROCESSED_ADDRESS_BOOK_IDS, array_map(function (AddressBook $addressBook) {
                return $addressBook->getId();
            }, $addressBooksToSynchronize));

        return $this->transport->getAddressBookContacts($addressBooksToSynchronize);
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.contact.label';
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
