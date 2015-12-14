<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RejectedContactExportIterator;

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
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->getRejectedExportImportIds($channel);

        $iterator = new AppendIterator();
        foreach ($imports as $import) {
            $exportContactsIterator = new RejectedContactExportIterator($this->managerRegistry, $import['importId']);
            $iterator->append($exportContactsIterator);
        }

        $this->setSourceIterator($iterator);
    }
}
