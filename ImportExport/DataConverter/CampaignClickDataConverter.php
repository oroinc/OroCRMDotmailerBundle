<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

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
