<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\State;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

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
            ->add('addressBookContacts.addressBook = :addressBook');

        $stateJoinCondition = $expr->andX()
            ->add('state.entityId = addressBookContacts.id')
            ->add('state.entityClass = :class')
            ->add('state.state = :state');

        $qb->select(
            [
                'addressBookContacts.id as addressBookContactId',
                'addressBookContacts.marketingListItemClass as entityClass',
                'contact.email',
                'contact.originId',
                'contact.dataFields',
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
            ->innerJoin(
                State::class,
                'state',
                Join::WITH,
                $stateJoinCondition
            );
        $qb->addOrderBy('contact.id');
        $qb->setParameter('addressBook', $addressBook);
        $qb->setParameter('state', State::STATE_SCHEDULED_FOR_EXPORT);
        $qb->setParameter('class', AddressBookContact::class);

        return $qb;
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
            )
            ->setParameter('marketingList', $marketingList);

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
        $created = new \DateTime('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('contact');
        $qb->delete()
            ->where('contact.channel = :channel')
            ->andWhere('contact.originId IS NULL')
            ->andWhere('contact.createdAt >= :created')
            ->getQuery()
            ->execute(['channel' => $channel, 'created' => $created->modify('-1 day')]);
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
