<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

interface ContactExportQBAdapterInterface
{
    /**
     * Must return query builder for getting not synced entities for export from database
     *
     * @param QueryBuilder $qb Base Query Builder contains MarketingListSegmentQuery
     * @param AddressBook  $addressBook
     *
     * @return QueryBuilder
     */
    public function prepareQueryBuilder(QueryBuilder $qb, AddressBook $addressBook);

    /**
     * @param AddressBook $addressBook
     *
     * @return bool
     */
    public function isApplicable(AddressBook $addressBook);
}
