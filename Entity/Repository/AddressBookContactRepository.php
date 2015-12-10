<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;

class AddressBookContactRepository extends EntityRepository
{
    /**
     * @param string $exportId
     * @param int $take
     * @param int $skip
     *
     * @return AddressBookContact[]
     */
    public function getAddressBookContactByExportId($exportId, $take, $skip)
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
        $qb = $this->createQueryBuilder('contact');
        $qb->update()
            ->where($qb->expr()->in('contact.id', $contactIds))
            ->set('contact.exportId', ':exportId')
            ->setParameter('exportId', $exportId)
            ->getQuery()
            ->execute();
    }
}
