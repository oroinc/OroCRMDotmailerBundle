<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;

class ImportWriter extends PersistentBatchWriter
{
    /**
     * @param array $items
     */
    public function write(array $items)
    {
        /**
         * clear new imported items list
         */
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);
        $context->setValue('newImportedItems', []);

        return parent::write($items);
    }
}
