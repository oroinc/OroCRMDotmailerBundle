<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

/**
 * Handles import of campaign activity records from Dotmailer.
 */
class CampaignActivityProcessor extends ImportProcessor
{
    #[\Override]
    public function process($item)
    {
        if (!$item) {
            return null;
        }

        return parent::process($item);
    }
}
