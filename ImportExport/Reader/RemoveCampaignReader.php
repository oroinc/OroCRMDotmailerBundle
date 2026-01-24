<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\ImportExport\Strategy\CampaignStrategy;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveCampaignIterator;

/**
 * Reads campaign data for removal operations during import processing.
 */
class RemoveCampaignReader extends AbstractReader
{
    #[\Override]
    protected function initializeReader()
    {
        $this->logger->info('Importing Removed Campaigns');
        $keepCampaigns = $this->jobContext->getValue(CampaignStrategy::EXISTING_CAMPAIGNS_ORIGIN_IDS);
        $keepCampaigns = $keepCampaigns ?: [];

        $iterator = new RemoveCampaignIterator($this->managerRegistry, $this->getChannel(), $keepCampaigns);

        $this->setSourceIterator($iterator);
    }
}
