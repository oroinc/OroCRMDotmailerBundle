<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;

/**
 * Clears a given cache when the entity manager is cleared.
 */
class CacheClearListener
{
    /** @var CacheProvider */
    private $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function onClear()
    {
        $this->cacheProvider->clearCache();
    }
}
