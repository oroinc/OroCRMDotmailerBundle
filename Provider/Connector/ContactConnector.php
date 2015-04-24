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
        $channel = $this->getChannel();
        $dateSince = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->getLastCreatedAt($channel);

        return $this->transport->getContacts($dateSince);
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
