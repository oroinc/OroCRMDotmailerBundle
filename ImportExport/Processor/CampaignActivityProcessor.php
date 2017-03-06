<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

class CampaignActivityProcessor extends ImportProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if (!$item) {
            return null;
        }

        return parent::process($item);
    }
}
