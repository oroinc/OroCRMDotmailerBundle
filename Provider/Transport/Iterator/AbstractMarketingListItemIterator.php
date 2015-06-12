<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractMarketingListItemIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /**
     * @var int
     */
    protected $batchSize = 1000;

    /**
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var ContextInterface
     */
    protected $importExportContext;

    /**
     * @param AddressBook                            $addressBook
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     * @param ContextInterface                       $importExportContext
     */
    public function __construct(
        AddressBook $addressBook,
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider,
        ContextInterface $importExportContext
    ) {
        $this->addressBook = $addressBook;
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;
        $this->importExportContext = $importExportContext;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $qb = $this->getIteratorQueryBuilder($this->addressBook);
        $qb->setMaxResults($take);

        $items = $qb
            ->getQuery()
            /**
             * Call multiple times during import
             * and because of it cache grows larger and script getting out of memory.
             */
            ->useQueryCache(false)
            ->execute();
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
