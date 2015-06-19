<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DotmailerEntitiesProvider
{
    const ADDRESS_BOOK_BY_CHANNEL_AND_ORIGIN_ID = 'addressBookByChannelAndOriginId';

    /**
     * @var CacheProvider
     */
    protected $cachingProvider;

    /**
     * @param CacheProvider $cachingProvider
     */
    public function __construct(CacheProvider $cachingProvider)
    {
        $this->cachingProvider = $cachingProvider;
    }

    public function getAddressBookByChannelAndOriginId(Channel $channel, $originId)
    {
        $key = "{$channel->getId()}__$originId";
        if ($addressBook = $this->cachingProvider->getCachedItem(self::ADDRESS_BOOK_BY_CHANNEL_AND_ORIGIN_ID, $key)) {
            return $addressBook;
        }


    }
}
