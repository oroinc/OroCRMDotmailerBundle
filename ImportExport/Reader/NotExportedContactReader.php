<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class NotExportedContactReader extends AbstractReader
{
    protected function initializeReader()
    {
        /** @var DotmailerTransport $transport */
        $transport = $this->contextMediator->getInitializedTransport($this->getChannel());

        $exportEntities = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(['channel' => $this->getChannel()]);

        $addressBookExports = [];

        foreach ($exportEntities as $exportEntity) {
            $addressBookExports[$exportEntity->getAddressBook()->getId()][] = $exportEntity->getImportId();
        }

        $iterator = new AppendIterator();
        foreach ($addressBookExports as $addressBookId => $importIds) {
            $iterator->append(
                $transport->getAddressBookExportReports($importIds, $addressBookId)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
