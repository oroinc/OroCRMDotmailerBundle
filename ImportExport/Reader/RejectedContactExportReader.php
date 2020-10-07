<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RejectedContactExportIterator;

/**
 * Export reader for rejected contacts
 */
class RejectedContactExportReader extends AbstractReader
{
    /**
     * {@inheritdoc}
     */
    protected function initializeReader()
    {
        if (!$channel = $this->getChannel()) {
            $channelId = $this->context->getOption('channel');
            throw new RuntimeException("Channel $channelId not exist");
        }

        $imports = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBookContactsExport')
            ->getRejectedExportImportIds($channel);

        $iterator = new AppendIterator();
        foreach ($imports as $import) {
            $exportContactsIterator = new RejectedContactExportIterator($this->managerRegistry, $import['importId']);
            $iterator->append($exportContactsIterator);
        }

        $this->setSourceIterator($iterator);
    }
}
