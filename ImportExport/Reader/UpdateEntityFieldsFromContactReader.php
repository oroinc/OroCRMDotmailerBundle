<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UpdateEntityFieldsFromContactIterator;

class UpdateEntityFieldsFromContactReader extends AbstractExportReader
{
    protected function initializeReader()
    {
        $this->logger->info('Updating entities data with contacts data fields based on mapping');
        $iterator = new AppendIterator();
        $addressBooks = $this->getAddressBooksToSync();

        foreach ($addressBooks as $addressBook) {
            $updateIterator = new UpdateEntityFieldsFromContactIterator(
                $addressBook,
                $this->marketingListItemsQueryBuilderProvider,
                $this->getContext()
            );
            $updateIterator->setRegistry($this->managerRegistry);
            $iterator->append($updateIterator);
        }

        $this->setSourceIterator($iterator);
    }
}
