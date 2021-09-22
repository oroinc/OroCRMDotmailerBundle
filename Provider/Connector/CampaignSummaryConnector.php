<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Campaign Summary Connector
 */
class CampaignSummaryConnector extends AbstractDotmailerConnector
{
    const TYPE = 'campaign_summary';
    const JOB_IMPORT = 'dotmailer_campaign_summary_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        //Synchronize only campaign activities that are connected with address book that are used within Oro.
        $campaignsToSynchronize = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Campaign')
            ->getCampaignsToSyncStatistic($this->getChannel());

        return $this->transport->getCampaignSummary($campaignsToSynchronize);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign_summary.label';
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
