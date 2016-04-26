<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class CampaignSummaryConnector extends AbstractDotmailerConnector
{
    const TYPE = 'campaign_summary';
    const JOB_IMPORT = 'dotmailer_campaign_summary_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        //Synchronize only campaign activities that are connected with address book that are used within OroCRM.
        $campaignsToSynchronize = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Campaign')
            ->getCampaignsToSyncStatistic($this->getChannel());

        return $this->transport->getCampaignSummary($campaignsToSynchronize);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.campaign_summary.label';
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
