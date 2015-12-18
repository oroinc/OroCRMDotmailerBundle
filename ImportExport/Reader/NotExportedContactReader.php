<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Guzzle\Iterator\AppendIterator;

use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class NotExportedContactReader extends AbstractReader
{
    protected function initializeReader()
    {
        if (!$channel = $this->getChannel()) {
            $channelId = $this->context->getOption('channel');
            throw new RuntimeException("Channel $channelId not exist");
        }

        /** @var DotmailerTransport $transport */
        $transport = $this->contextMediator->getInitializedTransport($channel);

        $addressBookExports = [];

        $exportEntities = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->getNotRejectedExports($channel);
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
