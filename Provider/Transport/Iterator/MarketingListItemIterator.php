<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\ContactSyncProcessor;

class MarketingListItemIterator extends AbstractMarketingListItemIterator
{
    /**
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        $currentItemsInBatch = $this->importExportContext
            ->getValue(ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS) ?: [];

        $failedToExportItems = $this->importExportContext
            ->getValue(ContactSyncProcessor::NOT_PROCESSED_ITEMS) ?: [];

        return $this->marketingListItemsQueryBuilderProvider
            ->getMarketingListItemsQB($addressBook, array_merge($currentItemsInBatch, $failedToExportItems));
    }

    /**
     * Clear cache after Append Iterator starts to iterate new iterator with different Address Book
     */
    public function rewind()
    {
        $this->importExportContext
            ->setValue(ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);
        $this->importExportContext
            ->setValue(ContactSyncProcessor::NOT_PROCESSED_ITEMS, []);

        parent::rewind();
    }
}
