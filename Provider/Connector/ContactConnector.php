<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class ContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'contact';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return 'OroCRM\Bundle\DotmailerBundle\Entity\Contact';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return 'new_dotmailer_contacts';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
