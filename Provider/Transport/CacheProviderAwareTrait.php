<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport;

use Doctrine\Common\Cache\CacheProvider;

trait CacheProviderAwareTrait
{
    /** @var CacheProvider */
    private $cache;

    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheProvider
     */
    public function getCache()
    {
        if (!$this->cache) {
            throw new \RuntimeException('CacheProvider not injected');
        }

        return $this->cache;
    }
}
