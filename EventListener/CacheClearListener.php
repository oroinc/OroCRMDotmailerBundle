<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\EventSubscriber;

use OroCRM\Bundle\DotmailerBundle\Provider\CacheProvider;

class CacheClearListener implements EventSubscriber
{
    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function onClear()
    {
        $this->cacheProvider->clearCache();
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
