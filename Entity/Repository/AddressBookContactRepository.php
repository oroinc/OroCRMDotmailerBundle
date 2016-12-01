<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;

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
            ->where($qb->expr()->in('address_book_contact.id', $contactIds))
            ->set('address_book_contact.exportId', ':exportId')
            ->setParameter('exportId', $exportId)
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
}
