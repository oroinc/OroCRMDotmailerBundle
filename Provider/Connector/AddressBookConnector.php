<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;

class AddressBookConnector extends AbstractDotmailerConnector
{
    const TYPE = 'address_book';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return new AddressBookIterator($this->transport);
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
        // TODO: Implement getImportJobName() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
