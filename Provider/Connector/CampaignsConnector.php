<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class CampaignsConnector extends AbstractDotmailerConnector
{
    const TYPE = 'campaign';

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
        // TODO: Implement getLabel() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        // TODO: Implement getImportEntityFQCN() method.
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
