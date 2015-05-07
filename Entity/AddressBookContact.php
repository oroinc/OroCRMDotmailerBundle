<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="orocrm_dm_ab_contact",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_ab_cnt_unq", columns={"address_book_id", "contact_id"})
 *     }
 * )
 */
class AddressBookContact
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
     * @ORM\ManyToOne(targetEntity="AddressBook")
     * @ORM\JoinColumn(name="address_book_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $addressBook;

    /**
     * Contact status
     *
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=16, nullable=false)
     */
    protected $status;

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
    public function setAddressBook(AddressBook $addressBook = null)
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
    public function setContact(Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return AddressBookContact
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
