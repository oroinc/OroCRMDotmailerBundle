<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\ContactSyncProcessor;

class ContactSyncWriter extends ImportWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);

        /**
         * Clear already read items raw values
         */
        $context->setValue(ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);

        parent::write($items);
    }
}
