<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractMarketingListItemIterator extends AbstractIterator
{
    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var int
     */
    protected $batchSize = 500;

    /**
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     */
    public function __construct(MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider)
    {
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        return $this->marketingListItemsQueryBuilderProvider->getMarketingListItemsQB($addressBook);
    }
}
