<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UpdateEntityFieldsFromContactIterator;

class UpdateEntityFieldsFromContactReader extends AbstractReader
{
    protected function initializeReader()
    {
        $this->logger->info('Updating entities data with contacts data fields based on mapping');

        $iterator = new UpdateEntityFieldsFromContactIterator($this->managerRegistry, $this->getChannel());

        $this->setSourceIterator($iterator);
    }
}
