<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy\DataFieldStrategy;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveDataFieldIterator;

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
