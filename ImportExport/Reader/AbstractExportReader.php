<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractExportReader extends AbstractReader
{
    const ADDRESS_BOOK_RESTRICTION_OPTION = 'address-book';

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var bool
     */
    protected $rewound = false;

    /**
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     *
     * @return RemovedContactExportReader
     */
    public function setMarketingListItemsQueryBuilderProvider(
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
    ) {
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;

        return $this;
    }

    /**
     * @return AddressBook[]
     */
    protected function getAddressBooksToSync()
    {
        $addressBookId = $this->context->getOption(self::ADDRESS_BOOK_RESTRICTION_OPTION);
        if ($addressBookId) {
            $addressBook = $this->registry
                ->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->find($addressBookId);
            if (!$addressBook) {
                throw new RuntimeException("Address book '{$addressBookId}' not found");
            }

            return [$addressBook];
        }

        $addressBooks = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSync($this->getChannel());
        return $addressBooks;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $iterator = $this->getSourceIterator();

        if (null === $this->getSourceIterator()) {
            throw new LogicException('Reader must be configured with source');
        }
        if (!$this->rewound) {
            $iterator->rewind();
            $this->rewound = true;
        } else {
            /**
             * Original reader method load next row after read current.
             * For MarketingListItemIterator last item will be not stored to
             * `ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS` and we can read already read item from the database.
             * Also we need to read new item from iterator only if it actually needed for prevent additional requests
             * to the DB or remote API.
             */
            $iterator->next();
        }

        $result = null;
        if ($iterator->valid()) {
            $result  = $iterator->current();
            $context = $this->getContext();
            $context->incrementReadOffset();
            $context->incrementReadCount();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceIterator(\Iterator $sourceIterator)
    {
        parent::setSourceIterator($sourceIterator);
        $this->rewound        = false;
    }
}
