<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class ContactRepository extends EntityRepository
{
    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    public function getScheduledForExportByChannelQB(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('contact');
        $expr = $qb->expr();
        $joinCondition = $expr->andX()
            ->add('addressBookContacts.addressBook =:addressBook')
            ->add($expr->eq('addressBookContacts.scheduledForExport', true));
        return $qb
            ->addSelect('opt_in_type, email_type')
            ->leftJoin('contact.opt_in_type', 'opt_in_type')
            ->leftJoin('contact.email_type', 'email_type')
            ->innerJoin(
                'contact.addressBookContacts',
                'addressBookContacts',
                Join::WITH,
                $joinCondition
            )
            ->setParameter('addressBook', $addressBook);
    }
}
