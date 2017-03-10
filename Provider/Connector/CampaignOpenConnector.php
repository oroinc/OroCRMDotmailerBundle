<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

class CampaignOpenConnector extends AbstractActivityConnector
{
    const TYPE = 'campaign_open';
    const JOB_IMPORT = 'dotmailer_campaign_open_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        if (!$this->isFeaturesEnabled()) {
            return new \EmptyIterator();
        }

        $campaignsToSynchronize = $this->getCampaignToSyncrhonize();

        return $this->transport->getCampaignOpens(
            $this->managerRegistry,
            $campaignsToSynchronize,
            $this->getLastSyncDate()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dotmailer.connector.campaign_open.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
