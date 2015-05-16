<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class RemovedContactsExportIterator extends AbstractMarketingListItemIterator
{
    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        return $this->marketingListItemsQueryBuilderProvider
            ->getRemovedMarketingListItemsQB($addressBook);
    }
}
