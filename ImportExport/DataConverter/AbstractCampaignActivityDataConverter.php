<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractActivityIterator;

abstract class AbstractCampaignActivityDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $defaultMapping =  [
            AbstractActivityIterator::MARKETING_CAMPAIGN_KEY => 'campaign:id',
            AbstractActivityIterator::ENTITY_ID_KEY => 'entityId',
            AbstractActivityIterator::ENTITY_CLASS_KEY => 'entityClass',
            AbstractActivityIterator::EMAIL_CAMPAIGN_KEY => 'relatedCampaignId',
            AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY => 'type:id',
        ];

        return array_merge($defaultMapping, $this->getSpecificHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }

    /**
     * Get conversion rules specific to activity
     *
     * @return array
     */
    abstract protected function getSpecificHeaderConversionRules();
}
