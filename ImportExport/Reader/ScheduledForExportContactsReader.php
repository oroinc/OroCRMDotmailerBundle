<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactsIterator;

class ScheduledForExportContactsReader extends AbstractExportReader
{
    protected function afterInitialize()
    {
        $iterator = new \AppendIterator();
        $addressBooks = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSync($this->getChannel());

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ScheduledForExportContactsIterator($addressBook, $this->registry)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
