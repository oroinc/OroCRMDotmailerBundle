<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class AddressBookContactRepository extends EntityRepository
{
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
     */
    public function bulkUpdateAddressBookContactsExportId(array $contactIds, $exportId)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
            ->where($qb->expr()->in('address_book_contact.id', ':contactIds'))
            ->setParameter('contactIds', $contactIds)
            ->set('address_book_contact.exportId', ':exportId')
            ->setParameter('exportId', $exportId)
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
            ->set(QueryBuilderUtil::getField('address_book_contact', $flagColumn), ':value')
            ->setParameter('value', $value)
            ->getQuery()
            ->execute();
    }

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
     */
    public function resetScheduledForEntityFieldUpdateFlag($contactIds)
    {
        $qb = $this->createQueryBuilder('address_book_contact');
        $qb->update()
           ->where($qb->expr()->in('address_book_contact.contact', ':contactIds'))
           ->set('address_book_contact.scheduledForFieldsUpdate', ':scheduledForFieldsUpdate')
           ->getQuery()
           ->execute(['contactIds' => $contactIds, 'scheduledForFieldsUpdate' => false]);
    }
}
