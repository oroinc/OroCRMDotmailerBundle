<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

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
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.address_book.label';
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
