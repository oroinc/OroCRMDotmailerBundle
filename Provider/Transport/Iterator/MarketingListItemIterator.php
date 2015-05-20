<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class MarketingListItemIterator extends AbstractMarketingListItemIterator
{
    /**
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        return $this->marketingListItemsQueryBuilderProvider
            ->getMarketingListItemsQB($addressBook);
    }
}
