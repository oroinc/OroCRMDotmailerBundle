<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class ContactSyncReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Preparing Contacts for Export');

        $iterator = new AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $marketingListItemIterator = new MarketingListItemIterator(
                $addressBook,
                $this->marketingListItemsQueryBuilderProvider,
                $this->getContext()
            );
            $iterator->append($marketingListItemIterator);
        }

        $this->setSourceIterator($iterator);
    }
}
