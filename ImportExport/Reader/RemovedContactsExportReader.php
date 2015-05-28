<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class RemovedContactsExportReader extends AbstractExportReader
{
    protected function initializeReader()
    {
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
