<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class CampaignConnector extends AbstractDotmailerConnector
{
    const TYPE = 'campaign';
    const JOB_IMPORT = 'dotmailer_campaign_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSyncOriginIds($this->getChannel());

        return $this->transport->getCampaigns($aBooksToSynchronize);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.campaign.label';
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
