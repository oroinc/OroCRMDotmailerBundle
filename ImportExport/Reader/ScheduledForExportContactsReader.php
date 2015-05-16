<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

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
                new RemovedContactsExportIterator($addressBook, $this->marketingListItemsQueryBuilderProvider)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
