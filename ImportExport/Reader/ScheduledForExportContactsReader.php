<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactsIterator;

class ScheduledForExportContactsReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $iterator = new \AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ScheduledForExportContactsIterator($addressBook, $this->registry)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
