<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Campaign Connector
 */
class CampaignConnector extends AbstractDotmailerConnector
{
    const TYPE = 'campaign';
    const JOB_IMPORT = 'dotmailer_campaign_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Campaigns.');
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->getSyncedAddressBooksToSyncOriginIds($this->getChannel());

        return $this->transport->getCampaigns($aBooksToSynchronize);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign.label';
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
