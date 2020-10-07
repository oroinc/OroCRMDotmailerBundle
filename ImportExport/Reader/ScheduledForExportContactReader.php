<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

/**
 * Export reader for contacts
 */
class ScheduledForExportContactReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Exporting Contacts');

        $iterator = new AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ScheduledForExportContactIterator($addressBook, $this->managerRegistry)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
