<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

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

    /**
     * @param array         $emails
     * @param MarketingList $marketingList
     *
     * @return bool
     */
    public function isUnsubscribedFromAddressBookByMarketingList(array $emails, MarketingList $marketingList)
    {
        $qb = $this->createQueryBuilder('contact');
        $expr = $qb->expr();
        $qb->select('COUNT(contact.id)')
            ->leftJoin('contact.addressBookContacts', 'addressBookContacts')
            ->leftJoin('addressBookContacts.addressBook', 'addressBook')
            ->where(
                $expr->eq('addressBook.marketingList', ':marketingList')
            )
            ->andWhere($expr->in('contact.email', $emails))
            ->andWhere(
                $expr->orX()
                    ->add($expr->eq('contact.status', ':status'))
                    ->add($expr->eq('addressBookContacts.status', ':status'))
            )->setParameters(
                [
                    'status'        => Contact::STATUS_UNSUBSCRIBED,
                    'marketingList' => $marketingList
                ]
            );

        return (bool)$qb->getQuery()
            ->getSingleScalarResult();
    }
}
