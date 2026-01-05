<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

/**
 * Campaign Connector
 */
class CampaignConnector extends AbstractDotmailerConnector
{
    public const TYPE = 'campaign';
    public const JOB_IMPORT = 'dotmailer_campaign_import';

    #[\Override]
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Campaigns.');
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository(AddressBook::class)
            ->getSyncedAddressBooksToSyncOriginIds($this->getChannel());

        return $this->transport->getCampaigns($aBooksToSynchronize);
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.campaign.label';
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
