<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractMarketingListItemIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /**
     * @var int
     */
    protected $batchSize = 500;

    /**
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @param AddressBook $addressBook
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     */
    public function __construct(
        AddressBook $addressBook,
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
    ) {
        $this->addressBook = $addressBook;
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        $qb = $this->getIteratorQueryBuilder($this->addressBook);
        $qb->setMaxResults($take);
        $qb->setFirstResult(++$skip);

        $items = $qb->getQuery()->execute();
        foreach ($items as &$item) {
            $item[static::ADDRESS_BOOK_KEY] = $this->addressBook->getOriginId();
        }
        return $items;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    abstract protected function getIteratorQueryBuilder(AddressBook $addressBook);
}
