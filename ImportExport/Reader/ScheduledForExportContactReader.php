<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

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
