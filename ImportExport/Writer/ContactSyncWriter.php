<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\ContactSyncProcessor;

class ContactSyncWriter extends ImportWriter
{
    /**
     * {@inheritdoc}
     */
    protected function clearTempValues(ContextInterface $context)
    {
        parent::clearTempValues($context);
        /**
         * Clear already read items raw values
         */
        $context->setValue(ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);
    }
}
