<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\State;

class AddressBookContactRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     *
     * @return AddressBookContact[]
     *
     * @deprecated
     * @see getAddressBookContactsScheduledToSyncForAddressBook
     */
    public function getAddressBookContactsScheduledToSync(Channel $channel)
    {
        return $this->getAddressBookContactsScheduledToSyncQB($channel)->getQuery()->getResult();
    }

    /**
     * @param Channel $channel
     *
     * @return QueryBuilder
     */
    private function getAddressBookContactsScheduledToSyncQB(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContact');
        $stateJoinCondition = $qb->expr()->andX()
            ->add('state.entityId = addressBookContact.id')
            ->add('state.entityClass = :class')
            ->add('state.state = :state');
        $qb->select()
            ->where('addressBookContact.channel = :channel')
            ->innerJoin(
                State::class,
                'state',
                Join::WITH,
                $stateJoinCondition
            );
        $qb->setParameter('channel', $channel);
        $qb->setParameter('state', State::STATE_SCHEDULED_FOR_EXPORT);
        $qb->setParameter('class', AddressBookContact::class);

        return $qb;
    }

    /**
     * @param Channel $channel
     * @param int $addressBookId
     *
     * @return AddressBookContact[]
     */
    public function getAddressBookContactsScheduledToSyncForAddressBook(Channel $channel, $addressBookId)
    {
        $qb = $this->getAddressBookContactsScheduledToSyncQB($channel);

        $qb
            ->andWhere('addressBookContact.addressBook = :addressBookId')
            ->setParameter('addressBookId', $addressBookId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $exportId
     * @param int $take
     * @param int $skip
     *
     * @return AddressBookContact[]
     */
    public function getAddressBookContactsByExportId($exportId, $take, $skip)
    {
        $qb = $this->createQueryBuilder('address_book_contact');

        return $qb->select('address_book_contact')
            ->innerJoin('address_book_contact.addressBook', 'address_book')
            ->addSelect('address_book')
            ->innerJoin('address_book_contact.contact', 'contact')
            ->addSelect('contact')
            ->innerJoin('address_book_contact.channel', 'channel')
            ->addSelect('channel')
            ->where('address_book_contact.exportId = :exportId')
            ->addOrderBy('address_book_contact.id')
            ->setMaxResults($take)
            ->setFirstResult($skip)
            ->getQuery()
            ->execute(['exportId' => $exportId]);
    }

    /**
     * @param array  $contactIds
     * @param string $exportId
     *
     * @deprecated
     * @see bulkUpdateAddressBookContactsExportIdForAddressBook
     */
    public function bulkUpdateAddressBookContactsExportId(array $contactIds, $exportId)
    {
        $this->bulkUpdateAddressBookContactsExportIdQB($contactIds, $exportId)->getQuery()->execute();
    }

    /**
     * @param array $contactIds
     * @param string $exportId
     *
     * @return QueryBuilder
     */
    public function bulkUpdateAddressBookContactsExportIdQB(array $contactIds, $exportId)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
            ->where($qb->expr()->in('address_book_contact.id', ':contactIds'))
            ->setParameter('contactIds', $contactIds)
            ->set('address_book_contact.exportId', ':exportId')
            ->setParameter('exportId', $exportId);

        return $qb;
    }

    /**
     * @param array  $contactIds
     * @param string $exportId
     * @param int $addressBookId
     */
    public function bulkUpdateAddressBookContactsExportIdForAddressBook(array $contactIds, $exportId, $addressBookId)
    {
        $qb = $this->bulkUpdateAddressBookContactsExportIdQB($contactIds, $exportId);

        $qb
            ->andWhere('address_book_contact.addressBook = :addressBookId')
            ->setParameter('addressBookId', $addressBookId)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string|array $entityClasses
     * @param Channel $channel
     */
    public function bulkUpdateEntityUpdatedFlag($entityClasses, Channel $channel)
    {
        $this->bulkUpdateFlagByEntity($entityClasses, $channel, 'entityUpdated');
    }

    /**
     * @param string|array $entityClasses
     * @param Channel $channel
     */
    public function bulkUpdateScheduledForEntityFieldUpdateFlag($entityClasses, Channel $channel)
    {
        $this->bulkUpdateFlagByEntity($entityClasses, $channel, 'scheduledForFieldsUpdate');
    }

    /**
     * @param string|array $entityClasses
     * @param string $flagColumn
     * @param Channel $channel
     * @param bool $value
     */
    public function bulkUpdateFlagByEntity($entityClasses, Channel $channel, $flagColumn, $value = true)
    {
        $entityClasses = (array) $entityClasses;
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
            ->where($qb->expr()->in('address_book_contact.marketingListItemClass', ':entityClasses'))
            ->setParameter('entityClasses', $entityClasses)
            ->andWhere('address_book_contact.channel = :channel')
            ->setParameter('channel', $channel)
            ->andWhere($qb->expr()->isNotNull('address_book_contact.marketingListItemId'))
            ->set('address_book_contact.' . $flagColumn, ':value')
            ->setParameter('value', $value)
            ->getQuery()
            ->execute();
    }

    /**
     * @param AddressBook $addressBook
     */
    public function bulkEntityUpdatedByAddressBook(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
            ->where('address_book_contact.addressBook = :addressBook')
            ->setParameter('addressBook', $addressBook)
            ->andWhere($qb->expr()->isNotNull('address_book_contact.marketingListItemId'))
            ->set('address_book_contact.entityUpdated', ':value')
            ->setParameter('value', true)
            ->getQuery()
            ->execute();
    }

    /**
     * Get entities classes of marketing lists where contact is present
     *
     * @param Contact $contact
     * @return array
     */
    public function getContactMarketingListItemClasses(Contact $contact)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb
            ->select('address_book_contact.marketingListItemClass as entityClass')
            ->distinct()
            ->where('address_book_contact.contact = :contact')
            ->setParameter('contact', $contact);
        
        $result = $qb->getQuery()->getArrayResult();
        if ($result) {
            $result = array_column($result, 'entityClass');
        }

        return $result;
    }

    /**
     * @param array $contactIds
     *
     * @deprecated
     * @see resetScheduledForEntityFieldUpdateFlagForAddressBook
     */
    public function resetScheduledForEntityFieldUpdateFlag($contactIds)
    {
        $this->resetScheduledForEntityFieldUpdateFlagQB($contactIds)->getQuery()->execute();
    }

    /**
     * @param array $contactIds
     *
     * @param QueryBuilder
     */
    public function resetScheduledForEntityFieldUpdateFlagQB(array $contactIds)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
            ->where($qb->expr()->in('address_book_contact.contact', ':contactIds'))
            ->set('address_book_contact.scheduledForFieldsUpdate', ':scheduledForFieldsUpdate')
            ->setParameter('contactIds', $contactIds)
            ->setParameter('scheduledForFieldsUpdate', false);

        return $qb;
    }

    /**
     * @param array $contactIds
     * @param int|null $addressBookId
     */
    public function resetScheduledForEntityFieldUpdateFlagForAddressBook(array $contactIds, $addressBookId = null)
    {
        $qb = $this->resetScheduledForEntityFieldUpdateFlagQB($contactIds);

        if ($addressBookId) {
            $qb->andWhere('address_book_contact.addressBook = :addressBookId')
                ->setParameter('addressBookId', $addressBookId);
        }

        $qb->getQuery()->execute();
    }
}
