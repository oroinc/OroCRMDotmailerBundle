<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class ActivityContactIterator extends AbstractIterator
{
    const CAMPAIGN_KEY = 'related_campaign';
    const LASTSYNCDATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * {@inheritdoc}
     */
    protected $batchSize = 100;

    /**
     * @var int
     */
    protected $campaignOriginId;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * @param IResources $dotmailerResources
     * @param int        $campaignOriginId
     * @param \DateTime  $lastSyncDate
     */
    public function __construct(IResources $dotmailerResources, $campaignOriginId, \DateTime $lastSyncDate)
    {
        $this->dotmailerResources = $dotmailerResources;
        $this->campaignOriginId = $campaignOriginId;
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
        $items = $this->dotmailerResources
            ->GetCampaignActivitiesSinceDateByDate(
                $this->campaignOriginId,
                $this->lastSyncDate->format(self::LASTSYNCDATE_FORMAT),
                $take,
                $skip
            );

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::CAMPAIGN_KEY] = $this->campaignOriginId;
        }

        return $items;
    }
}
