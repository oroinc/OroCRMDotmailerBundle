<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

class NotExportedContactStrategy extends AbstractImportStrategy
{

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $addressBookContact = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact');
    }
}
