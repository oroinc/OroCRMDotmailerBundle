<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * Trait for cache provider
 */
trait CacheProviderAwareTrait
{
    private ?CacheInterface $cache = null;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): CacheInterface
    {
        if (!$this->cache) {
            throw new \RuntimeException('CacheProvider not injected');
        }

        return $this->cache;
    }
}
