<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class ActivityContactIterator extends AbstractIterator
{
    const CAMPAIGN_KEY = 'related_campaign';

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * {@inheritdoc}
     */
    protected $batchSize = 1000;

    /**
     * @var int
     */
    protected $campaignOriginId;

    /**
     * @var bool
     */
    protected $isInit;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * @param IResources $dotmailerResources
     * @param int        $campaignOriginId
     * @param bool       $isInit
     * @param \DateTime  $lastSyncDate
     */
    public function __construct(
        IResources $dotmailerResources,
        $campaignOriginId,
        $isInit = false,
        \DateTime $lastSyncDate = null
    ) {
        $this->dotmailerResources = $dotmailerResources;
        $this->campaignOriginId = $campaignOriginId;
        $this->isInit = $isInit;
        $this->lastSyncDate = $lastSyncDate;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        if (!$this->isInit || is_null($this->lastSyncDate)) {
            $items = $this->dotmailerResources->GetCampaignActivities($this->campaignOriginId, $take, $skip);
        } else {
            $items = $this->dotmailerResources->GetCampaignActivitiesSinceDateByDate(
                $this->campaignOriginId,
                $this->lastSyncDate->format(\DateTime::ISO8601),
                $take,
                $skip
            );
        }

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::CAMPAIGN_KEY] = $this->campaignOriginId;
        }

        return $items;
    }
}
