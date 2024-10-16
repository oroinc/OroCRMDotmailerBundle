<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Campaign Clicks Connector
 */
class CampaignClickConnector extends AbstractActivityConnector
{
    const TYPE = 'campaign_click';
    const JOB_IMPORT = 'dotmailer_campaign_click_import';

    #[\Override]
    protected function getConnectorSource()
    {
        if (!$this->isFeaturesEnabled()) {
            return new \EmptyIterator();
        }

        $campaignsToSynchronize = $this->getCampaignToSyncrhonize();

        return $this->transport->getCampaignClicks(
            $this->managerRegistry,
            $campaignsToSynchronize,
            $this->getLastSyncDate()
        );
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign_click.label';
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }
}
