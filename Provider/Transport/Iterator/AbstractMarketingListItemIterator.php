<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

abstract class AbstractMarketingListItemIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /**
     * @var int
     */
    protected $batchSize = 1000;

    /**
     * @var int
     */
    protected $addressBookId;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var ContextInterface
     */
    protected $importExportContext;

    public function __construct(
        AddressBook $addressBook,
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider,
        ContextInterface $importExportContext
    ) {
        $this->addressBookId = $addressBook->getId();
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;
        $this->importExportContext = $importExportContext;
    }

    /**
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        return $this->marketingListItemsQueryBuilderProvider->getAddressBook($this->addressBookId);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $qb = $this->getIteratorQueryBuilder($this->getAddressBook());
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
            $item[static::ADDRESS_BOOK_KEY] = $this->getAddressBook()->getOriginId();
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
