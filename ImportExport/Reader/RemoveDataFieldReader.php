<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\ImportExport\Strategy\DataFieldStrategy;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveDataFieldIterator;

class RemoveDataFieldReader extends AbstractReader
{
    protected function initializeReader()
    {
        $this->logger->info('Importing Removed Data Fields');
        $keepDataFieldsNames = $this->jobContext->getValue(DataFieldStrategy::EXISTING_DATAFIELDS_NAMES) ?: [];

        $iterator = new RemoveDataFieldIterator($this->managerRegistry, $this->getChannel(), $keepDataFieldsNames);

        $this->setSourceIterator($iterator);
    }
}
