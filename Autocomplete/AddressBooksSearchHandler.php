<?php

namespace Oro\Bundle\DotmailerBundle\Autocomplete;

/**
 * The autocomplete handler to search dotmailer address books.
 */
class AddressBooksSearchHandler extends ChannelAwareSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        list($searchTerm, $channelId, $marketingListId) = explode(';', $search);

        $queryBuilder = $this->prepareQueryBuilder($searchTerm, $channelId, $firstResult, $maxResults);
        $queryBuilder
            ->andWhere('e.marketingList IS NULL or e.marketingList =:marketingList')
            ->setParameter('marketingList', (int)$marketingListId);

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}
