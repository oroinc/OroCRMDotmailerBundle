<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractActivityIterator;

/**
 * Provides common functionality for converting Dotmailer campaign activity data.
 *
 * This base class implements the core header conversion rules shared across different campaign activity types
 * (e.g., opens, clicks, bounces). It maps activity data fields to the internal entity structure.
 * Subclasses must implement activity-specific header conversion rules.
 */
abstract class AbstractCampaignActivityDataConverter extends AbstractDataConverter
{
    #[\Override]
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

    #[\Override]
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
