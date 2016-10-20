<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\ImportExport\Strategy\AddressBookStrategy;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveAddressBookIterator;

class RemoveAddressBookReader extends AbstractReader
{
    protected function initializeReader()
    {
        $this->logger->info('Importing Removed Address Books');
        $keepAddressBooks = $this->jobContext->getValue(AddressBookStrategy::EXISTING_ADDRESS_BOOKS_ORIGIN_IDS);
        $keepAddressBooks = $keepAddressBooks ?: [];

        $iterator = new RemoveAddressBookIterator($this->managerRegistry, $this->getChannel(), $keepAddressBooks);

        $this->setSourceIterator($iterator);
    }
}
