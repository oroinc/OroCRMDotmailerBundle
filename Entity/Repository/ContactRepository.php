<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Utils\EmailUtils;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
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
                    'addressBookContacts.marketingListItemClass as entityClass',
                    'contact.email',
                    'contact.originId',
                    'contact.dataFields',
                    "JSON_EXTRACT(contact.serialized_data, 'opt_in_type') as optInType",
                    "JSON_EXTRACT(contact.serialized_data, 'email_type') as emailType",
                ]
            )
            ->innerJoin(
                'contact.addressBookContacts',
                'addressBookContacts',
                Join::WITH,
                $joinCondition
            )
            ->setParameter('addressBook', $addressBook)
            ->addOrderBy('contact.id');
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
            ->andWhere($expr->in($expr->lower('contact.email'), EmailUtils::getLowerCaseEmails($emails)))
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
            ->setParameter('emails', EmailUtils::getLowerCaseEmails($emails))
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($result as $record) {
            $map[$record['email']] = $record['originId'];
        }

        return $map;
    }

    public function bulkRemoveNotExportedContacts(Channel $channel)
    {
        $qb = $this->createQueryBuilder('contact');
        $qb->delete()
            ->where('contact.channel = :channel')
            ->andWhere('contact.originId IS NULL')
            ->getQuery()
            ->execute(['channel' => $channel]);
    }

    /**
     * Get contacts with data fields updates, which should be synced into entities
     *
     * @param AddressBook $addressBook
     * @return QueryBuilder
     */
    public function getScheduledForEntityFieldsUpdateQB(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('contact');
        $expr = $qb->expr();
        $joinCondition = $expr->andX()
            ->add('addressBookContacts.addressBook =:addressBook')
            ->add($expr->isNotNull('addressBookContacts.marketingListItemClass'));

        $qb
            ->select(
                [
                    'contact.id as contactId',
                    'contact.originId',
                    'contact.email',
                    'contact.dataFields',
                    'addressBookContacts.marketingListItemClass as entityClass',
                    'addressBookContacts.marketingListItemId as entityId',
                ]
            )
            ->innerJoin(
                'contact.addressBookContacts',
                'addressBookContacts',
                Join::WITH,
                $joinCondition
            )
            ->andWhere(
                $expr->andX()
                   ->add('addressBookContacts.scheduledForFieldsUpdate = :isScheduled')
                   ->add($expr->isNotNull('addressBookContacts.marketingListItemId'))
            )
            ->setParameter('isScheduled', true)
            ->setParameter('addressBook', $addressBook)
            ->addOrderBy('contact.id');

        return $qb;
    }

    /**
     * @param array $originIds
     * @param array $addressBooks
     * @return array
     */
    public function getEntitiesDataByOriginIds(array $originIds, array $addressBooks = [])
    {
        $qb = $this->createQueryBuilder('contact');

        $qb
            ->select(
                [
                    'contact.originId',
                    'addressBookContacts.marketingListItemClass as entityClass',
                    'addressBookContacts.marketingListItemId as entityId',
                ]
            )
            ->innerJoin('contact.addressBookContacts', 'addressBookContacts')
            ->where($qb->expr()->in('contact.originId', ':originIds'))
            ->setParameter('originIds', $originIds)
            ->andWhere($qb->expr()->isNotNull('addressBookContacts.marketingListItemId'));
        if ($addressBooks) {
            $qb->andWhere($qb->expr()->in('addressBookContacts.addressBook', ':addressBooks'))
                ->setParameter('addressBooks', $addressBooks);
        };
        $result = $qb->getQuery()->getArrayResult();
        //make sure entity is added to the result only 1 time
        $uniqueItems = [];
        $items = [];
        foreach ($result as $item) {
            if (!isset($uniqueItems[$item['entityClass']][$item['entityId']])) {
                $items[] = $item;
                $uniqueItems[$item['entityClass']][$item['entityId']] = true;
            }
        }

        return $items;
    }
}
