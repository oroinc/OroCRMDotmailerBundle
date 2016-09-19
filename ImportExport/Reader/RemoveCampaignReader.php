<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\ImportExport\Strategy\CampaignStrategy;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveCampaignIterator;

class RemoveCampaignReader extends AbstractReader
{
    protected function initializeReader()
    {
        $this->logger->info('Importing Removed Campaigns');
        $keepCampaigns = $this->jobContext->getValue(CampaignStrategy::EXISTING_CAMPAIGNS_ORIGIN_IDS);
        $keepCampaigns = $keepCampaigns ?: [];

        $iterator = new RemoveCampaignIterator($this->managerRegistry, $this->getChannel(), $keepCampaigns);

        $this->setSourceIterator($iterator);
    }
}
