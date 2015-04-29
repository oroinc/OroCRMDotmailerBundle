<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class CampaignSummaryIterator extends AbstractIterator
{
    const CAMPAIGN_KEY = 'related_campaign';

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
     * @param IResources $dotmailerResources
     * @param int        $campaignOriginId
     */
    public function __construct(IResources $dotmailerResources, $campaignOriginId)
    {
        $this->dotmailerResources = $dotmailerResources;
        $this->campaignOriginId = $campaignOriginId;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        $item = $this->dotmailerResources->GetCampaignSummary($this->campaignOriginId);
        if ($item) {
            $item = $item->toArray();
            $item[self::CAMPAIGN_KEY] = $this->campaignOriginId;
        }

        return [$item];
    }
}
