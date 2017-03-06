<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

class CampaignOpenDataConverter extends AbstractCampaignActivityDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getSpecificHeaderConversionRules()
    {
        return [
            'dateopened' => 'actionDate',
        ];
    }
}
