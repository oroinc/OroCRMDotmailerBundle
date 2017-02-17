<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class CampaignOpenIterator extends AbstractActivityIterator
{
    /**
     * {@inheritdoc}
     */
    protected function getAllActivities($take, $skip)
    {
        $items = $this->dotmailerResources->GetCampaignOpens($this->campaignOriginId, $take, $skip);

        return $items;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function getMarketingActivityType()
    {
        return MarketingActivity::TYPE_OPEN;
    }
}
