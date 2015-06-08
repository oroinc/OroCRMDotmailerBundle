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

        $exportEntities = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(['channel' => $this->getChannel()]);

        $addressBookExports = [];

        foreach ($exportEntities as $exportEntity) {
            $addressBookId = $exportEntity->getAddressBook()->getId();
            if (!isset($addressBookExports[$addressBookId])) {
                $addressBookExports[$addressBookId] = [];
            }

            $addressBookExports[$addressBookId][] = $exportEntity->getImportId();
        }

        $iterator = new AppendIterator();
        foreach ($addressBookExports as $addressBookId => $importIds) {
            $iterator->append(
                $transport->getAddressBookExportReports($addressBookId, $importIds)
            );
        }

        $this->setSourceIterator($iterator);
    }
}
