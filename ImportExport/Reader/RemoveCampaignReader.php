<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy\CampaignStrategy;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveCampaignIterator;

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
