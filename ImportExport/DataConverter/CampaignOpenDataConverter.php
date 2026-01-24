<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

/**
 * Data converter for campaign open activity import/export.
 *
 * Converts campaign open activity data between Dotmailer format and internal representation.
 */
class CampaignOpenDataConverter extends AbstractCampaignActivityDataConverter
{
    #[\Override]
    protected function getSpecificHeaderConversionRules()
    {
        return [
            'dateopened' => 'actionDate',
        ];
    }
}
