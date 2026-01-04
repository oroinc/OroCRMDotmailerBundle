<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Campaign Open Connector
 */
class CampaignOpenConnector extends AbstractActivityConnector
{
    public const TYPE = 'campaign_open';
    public const JOB_IMPORT = 'dotmailer_campaign_open_import';

    #[\Override]
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

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign_open.label';
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
