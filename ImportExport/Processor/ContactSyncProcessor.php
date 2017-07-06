<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

class ContactSyncProcessor extends ImportProcessor
{
    const CURRENT_BATCH_READ_ITEMS = 'currentBatchReadItems';

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        $items = $this->context->getValue(self::CURRENT_BATCH_READ_ITEMS) ?: [];
        $items[] = $item;

        $this->context->setValue(self::CURRENT_BATCH_READ_ITEMS, $items);

        $processedItem = parent::process($item);

        if (is_null($processedItem)) {
            $items = $this->context->getValue(self::NOT_PROCESSED_ITEMS) ?: [];
            $items[] = $item;
            $this->context->setValue(self::NOT_PROCESSED_ITEMS, $items);
        }

        return $processedItem;
    }
}
