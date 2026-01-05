<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Entity\Campaign;

/**
 * Campaign Summary Connector
 */
class CampaignSummaryConnector extends AbstractDotmailerConnector
{
    public const TYPE = 'campaign_summary';
    public const JOB_IMPORT = 'dotmailer_campaign_summary_import';

    #[\Override]
    protected function getConnectorSource()
    {
        //Synchronize only campaign activities that are connected with address book that are used within Oro.
        $campaignsToSynchronize = $this->managerRegistry
            ->getRepository(Campaign::class)
            ->getCampaignsToSyncStatistic($this->getChannel());

        return $this->transport->getCampaignSummary($campaignsToSynchronize);
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign_summary.label';
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
