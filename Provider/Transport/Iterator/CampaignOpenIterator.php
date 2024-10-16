<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

/**
 * CampaignOpenIterator class
 */
class CampaignOpenIterator extends AbstractActivityIterator
{
    #[\Override]
    protected function getAllActivities($take, $skip)
    {
        $items = $this->dotmailerResources->GetCampaignOpens($this->campaignOriginId, $take, $skip);

        return $items;
    }

    #[\Override]
    protected function getActivitiesSinceDate($take, $skip)
    {
        if (!$this->additionalResource) {
            throw new RuntimeException('The API method is not defined.');
        }
        $items = $this->additionalResource->getCampaignOpensSinceDateByDate(
            $this->campaignOriginId,
            $this->lastSyncDate->format(\DateTime::ISO8601),
            $take,
            $skip
        );

        return $items;
    }

    #[\Override]
    protected function getMarketingActivityType(): string
    {
        return ExtendHelper::buildEnumOptionId(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_OPEN
        );
    }
}
