<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class ContactsSyncReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Preparing Contacts for Export');

        $iterator = new \AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new MarketingListItemIterator($addressBook, $this->marketingListItemsQueryBuilderProvider)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
