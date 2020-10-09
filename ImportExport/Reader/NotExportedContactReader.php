<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;

/**
 * Export reader for not synced contacts
 */
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
            ->getRepository('OroDotmailerBundle:AddressBookContactsExport')
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
