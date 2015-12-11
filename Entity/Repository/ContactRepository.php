<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
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
            ->add('addressBookContacts.scheduledForExport = TRUE');

        return $qb
            ->select(
                [
                    'addressBookContacts.id as addressBookContactId',
                    'contact.email',
                    'contact.originId',
                    'contact.firstName',
                    'contact.lastName',
                    'contact.gender',
                    'contact.fullName',
                    'contact.postcode',
                    'opt_in_type.id as optInType',
                    'email_type.id as emailType',
                ]
            )
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
        $subscribedStatuses = [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED];

        $qb->select('COUNT(contact.id)')
            ->leftJoin('contact.addressBookContacts', 'addressBookContacts')
            ->leftJoin('addressBookContacts.addressBook', 'addressBook')
            ->where(
                $expr->eq('addressBook.marketingList', ':marketingList')
            )
            ->andWhere($expr->in('contact.email', $emails))
            ->andWhere(
                $expr->orX()
                    ->add($expr->notIn('contact.status', $subscribedStatuses))
                    ->add($expr->notIn('addressBookContacts.status', $subscribedStatuses))
            )->setParameters(
                [
                    'marketingList' => $marketingList
                ]
            );

        return (bool)$qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array $emails
     *
     * @return array associative array with emails as key and dotmailer contact ID as a value
     */
    public function getContactIdsByEmails(array $emails)
    {
        $qb = $this->createQueryBuilder('contact');

        $result = $qb
            ->select('contact.originId, contact.email')
            ->where($qb->expr()->in('contact.email', ':emails'))
            ->setParameter('emails', $emails)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($result as $record) {
            $map[$record['email']] = $record['originId'];
        }

        return $map;
    }

    /**
     * @param Channel $channel
     */
    public function bulkRemoveNotExportedContacts(Channel $channel)
    {
        $qb = $this->createQueryBuilder('contact');
        $qb->delete()
            ->where('contact.channel = :channel')
            ->andWhere('contact.originId IS NULL')
            ->getQuery()
            ->execute(['channel' => $channel]);
    }
}
