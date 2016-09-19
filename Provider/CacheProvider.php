<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

class CacheProvider
{
    /**
     * @var array
     */
    protected $itemsCache = [];

    /**
     * @param string|int $scopeKey
     * @param string|int $itemKey
     *
     * @return mixed|null
     */
    public function getCachedItem($scopeKey, $itemKey)
    {
        return isset($this->itemsCache[$scopeKey][$itemKey]) ? $this->itemsCache[$scopeKey][$itemKey] : null;
    }

    /**
     * @param string|int $scopeKey
     * @param string|int $itemKey
     * @param object     $value Entity for store in cache
     *
     * @return CacheProvider
     */
    public function setCachedItem($scopeKey, $itemKey, $value)
    {
        $this->itemsCache[$scopeKey][$itemKey] = $value;

        return $this;
    }

    public function clearCache()
    {
        $this->itemsCache = [];
    }
}
