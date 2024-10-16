<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

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
