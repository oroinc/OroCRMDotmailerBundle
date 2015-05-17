<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class AddressBookContactRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     * @return QueryBuilder
     */
    public function getScheduledForExportByChannelQB(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContact');
        $expr = $qb->expr();
        $whereExpression = $expr->andX();
        $whereExpression->add($expr->eq('contact.channel', $channel));
        $whereExpression->add($expr->eq('addressBookContact.scheduledForExport', true));
        return $qb
            ->innerJoin('addressBookContact.contact', 'contact')
            ->innerJoin('addressBookContact.addressBook', 'addressBook')
            ->where($whereExpression)
            ->orderBy('addressBook.id');
    }
}
