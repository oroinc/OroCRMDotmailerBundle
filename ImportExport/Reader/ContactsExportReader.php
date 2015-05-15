<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactExportIterator;

class ContactsExportReader extends AbstractExportReader
{

    protected function afterInitialize()
    {
        $iterator = new \AppendIterator();
        $addressBooks = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSync($this->getChannel());

        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ContactExportIterator($addressBook, $this->marketingListItemsQueryBuilderProvider)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
