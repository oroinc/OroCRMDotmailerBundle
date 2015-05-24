<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;

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
     * @param ManagerRegistry $registry
     *
     * @return RemovedContactsExportReader
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     *
     * @return RemovedContactsExportReader
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
}
