<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

/**
 * Export reader for removed contacts
 */
class RemovedContactExportReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Exporting Removed Contacts');

        $iterator = new AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new RemovedContactsExportIterator(
                    $addressBook,
                    $this->marketingListItemsQueryBuilderProvider,
                    $this->getContext()
                )
            );
        }

        $this->setSourceIterator($iterator);
    }
}
