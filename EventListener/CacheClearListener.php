<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\EventSubscriber;

use OroCRM\Bundle\DotmailerBundle\Provider\CacheProvider;

class CacheClearListener implements EventSubscriber
{
    /**
     * @var CacheProvider
     */
    protected $campaignStatisticCachingProvider;

    /**
     * @param CacheProvider $campaignStatisticCachingProvider
     */
    public function __construct(CacheProvider $campaignStatisticCachingProvider)
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
