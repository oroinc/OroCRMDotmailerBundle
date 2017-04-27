<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

class CampaignClickDataConverter extends AbstractCampaignActivityDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getSpecificHeaderConversionRules()
    {
        return [
            'dateclicked' => 'actionDate',
            'url' => 'details'
        ];
    }
}
