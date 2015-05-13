<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class AddressBookContactsRepository extends EntityRepository
{
    /**
     * @param Contact     $contact
     * @param AddressBook $addressBook
     *
     * @return array
     */
    public function getAddressBookContact(Contact $contact, AddressBook $addressBook)
    {
        return $this->createQueryBuilder('addressBookContact')
            ->where('addressBookContact.addressBook = :addressBook')
            ->andWhere('addressBookContact.contact = :contact')
            ->setParameters([ 'contact' => $contact, 'addressBook' => $addressBook ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
