<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\UnsubscribedContactSyncProcessor;

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
            $item[self::MARKETING_LIST] = $this->addressBook->getMarketingList();
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
    public function rewind()
    {
        $this->importExportContext
            ->setValue(UnsubscribedContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);

        parent::rewind();
    }
}
