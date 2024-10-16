<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Address book Connector
 */
class AddressBookConnector extends AbstractDotmailerConnector
{
    const TYPE = 'address_book';
    const IMPORT_JOB = 'dotmailer_address_book_import';

    #[\Override]
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Address Books.');

        return $this->transport->getAddressBooks();
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.address_book.label';
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
