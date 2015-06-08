<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractExportReader extends AbstractReader
{
    const ADDRESS_BOOK_RESTRICTION_OPTION = 'address-book';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var bool
     */
    protected $rewound = false;

    /**
     * @param ManagerRegistry $registry
     *
     * @return RemovedContactExportReader
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

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
        $addressBook = $this->context->getOption(self::ADDRESS_BOOK_RESTRICTION_OPTION);
        if ($addressBook) {
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
}
