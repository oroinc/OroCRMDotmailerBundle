<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use OroCRM\Bundle\DotmailerBundle\Model\ExtendAddressBookContact;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="orocrm_dm_ab_contact",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_ab_cnt_unq", columns={"address_book_id", "contact_id"})
 *     }
 * )
 * @Config()
 */
class AddressBookContact extends ExtendAddressBookContact
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AddressBook
     *
     * @ORM\ManyToOne(targetEntity="AddressBook", inversedBy="addressBookContacts")
     * @ORM\JoinColumn(name="address_book_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $addressBook;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="addressBookContacts")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $contact;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="unsubscribed_date", type="datetime", nullable=true)
     */
    protected $unsubscribedDate;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AddressBook
     */
    public function getAddressBook()
    {
        return $this->addressBook;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return AddressBookContact
     */
    public function setAddressBook(AddressBook $addressBook)
    {
        $this->addressBook = $addressBook;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return AddressBookContact
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUnsubscribedDate()
    {
        return $this->unsubscribedDate;
    }

    /**
     * @param \DateTime $unsubscribedDate
     *
     * @return AddressBookContact
     */
    public function setUnsubscribedDate(\DateTime $unsubscribedDate = null)
    {
        $this->unsubscribedDate = $unsubscribedDate;

        return $this;
    }
}
