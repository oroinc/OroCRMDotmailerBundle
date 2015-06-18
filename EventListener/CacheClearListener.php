<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\EventSubscriber;

use OroCRM\Bundle\DotmailerBundle\Provider\CampaignStatisticCachingProvider;

class CacheClearListener implements EventSubscriber
{
    /**
     * @var CampaignStatisticCachingProvider
     */
    protected $campaignStatisticCachingProvider;

    /**
     * @param CampaignStatisticCachingProvider $campaignStatisticCachingProvider
     */
    public function __construct(CampaignStatisticCachingProvider $campaignStatisticCachingProvider)
    {
        $this->campaignStatisticCachingProvider = $campaignStatisticCachingProvider;
    }

    public function onClear()
    {
        $this->campaignStatisticCachingProvider->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'onClear'
        ];
    }
}
