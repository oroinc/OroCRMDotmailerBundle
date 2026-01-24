<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

/**
 * Data converter for campaign click activity import/export.
 *
 * Converts campaign click activity data between Dotmailer format and internal representation.
 */
class CampaignClickDataConverter extends AbstractCampaignActivityDataConverter
{
    #[\Override]
    protected function getSpecificHeaderConversionRules()
    {
        return [
            'dateclicked' => 'actionDate',
            'url' => 'details'
        ];
    }
}
