<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

class RemovedContactsExportIterator extends AbstractMarketingListItemIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';
    /**
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * @param AddressBook $addressBook
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     */
    public function __construct(
        AddressBook $addressBook,
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
    ) {
        $this->addressBook = $addressBook;
        parent::__construct($marketingListItemsQueryBuilderProvider);
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        return $this->marketingListItemsQueryBuilderProvider
            ->getRemovedMarketingListItemsQB($addressBook);
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
            $item[self::ADDRESS_BOOK_KEY] = $this->addressBook->getOriginId();
        }
        return $items;
    }
}
