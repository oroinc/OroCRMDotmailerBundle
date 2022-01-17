<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\ImportExport\Processor\UnsubscribedContactSyncProcessor;

/**
 * Iterates over marketing lists that are out of sync
 */
class OutOfSyncMarketingListItemIterator extends AbstractMarketingListItemIterator
{
    const MARKETING_LIST = 'marketingList';

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $items = parent::getItems($take, $skip);
        foreach ($items as &$item) {
            $marketingList = $this->getAddressBook()->getMarketingList();
            $item[self::MARKETING_LIST] = $marketingList ? $marketingList->getId() : false;
        }
        return $items;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        $currentItemsInBatch = $this->importExportContext
            ->getValue(UnsubscribedContactSyncProcessor::CURRENT_BATCH_READ_ITEMS) ?: [];

        return $this->marketingListItemsQueryBuilderProvider
            ->getOutOfSyncMarketingListItemsQB($addressBook, $currentItemsInBatch);
    }

    /**
     * Clear cache after Append Iterator starts to iterate new iterator with different Address Book
     */
    public function rewind(): void
    {
        $this->importExportContext
            ->setValue(UnsubscribedContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);

        parent::rewind();
    }
}
