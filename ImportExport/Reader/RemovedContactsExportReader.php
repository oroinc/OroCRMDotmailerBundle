<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class RemovedContactsExportReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Exporting Removed Contacts');

        $iterator = new \AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new RemovedContactsExportIterator($addressBook, $this->marketingListItemsQueryBuilderProvider)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
