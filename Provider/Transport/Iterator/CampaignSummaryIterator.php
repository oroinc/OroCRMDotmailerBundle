<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Collections\Collection;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;

/**
 * Iterates over campaigns summaries
 */
class CampaignSummaryIterator implements \Iterator
{
    const CAMPAIGN_KEY = 'related_campaign';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $currentItemIndex = 0;

    /**
     * @var bool
     */
    protected $isValid = true;

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var Collection|Campaign[]
     */
    protected $campaigns;

    /**
     * @param IResources $dotmailerResources
     * @param mixed      $campaigns
     */
    public function __construct(IResources $dotmailerResources, $campaigns)
    {
        $this->dotmailerResources = $dotmailerResources;
        $this->campaigns = $campaigns;
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $items = [];
        foreach ($this->campaigns as $campaign) {
            $item = $this->dotmailerResources->GetCampaignSummary($campaign->getOriginId());
            if ($item) {
                $item = $item->toArray();
                $item[self::CAMPAIGN_KEY] = $campaign->getId();
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return current($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (next($this->items) !== false) {
            $this->currentItemIndex++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->currentItemIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $isValid = $this->isValid && current($this->items) !== false;
        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->items = $this->getItems();
        reset($this->items);
        $this->currentItemIndex = 0;

        $this->isValid = count($this->items) > 0;
    }
}
