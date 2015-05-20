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
            ->select(
                [
                    'contact.email',
                    'contact.originId',
                    'contact.firstName',
                    'contact.lastName',
                    'contact.gender',
                    'contact.fullName',
                    'contact.postcode',
                    'optInType.id as opt_in_type',
                    'emailType.id as email_type',
                ]
            )
            ->leftJoin('contact.opt_in_type', 'optInType')
            ->leftJoin('contact.email_type', 'emailType')
            ->innerJoin(
                'contact.addressBookContacts',
                'addressBookContacts',
                Join::WITH,
                $joinCondition
            )
            ->setParameter('addressBook', $addressBook);
    }
}
