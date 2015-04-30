<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class ContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'contact';
    const IMPORT_JOB = 'dotmailer_new_contacts';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getContacts($this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.contact.label';
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
