<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Address book Connector
 */
class AddressBookConnector extends AbstractDotmailerConnector
{
    const TYPE = 'address_book';
    const IMPORT_JOB = 'dotmailer_address_book_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Address Books.');

        return $this->transport->getAddressBooks();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.address_book.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
