<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Address book contact entity.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository")
 * @ORM\Table(
 *      name="orocrm_dm_ab_contact",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_ab_cnt_unq", columns={"address_book_id", "contact_id"})
 *     },
 *     indexes={
 *          @ORM\Index(name="orocrm_dm_ab_cnt_export_id_idx", columns={"export_id"}),
 *          @ORM\Index(
 *                  name="IDX_MARKETING_LIST_ITEM_CLASS_ID",
 *                  columns={"marketing_list_item_class", "marketing_list_item_id"}
 *          ),
 *     }
 * )
 * @Config()
 * @method AbstractEnumValue getStatus()
 * @method AddressBookContact setStatus(AbstractEnumValue $enumValue)
 * @method AbstractEnumValue getExportOperationType()
 * @method AddressBookContact setExportOperationType(AbstractEnumValue $enumValue)
 */
class AddressBookContact implements ChannelAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    const EXPORT_NEW_CONTACT = 'new';
    const EXPORT_ADD_TO_ADDRESS_BOOK = 'add';
    const EXPORT_UPDATE_CONTACT = 'update';

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
     * @var int
     *
     * @ORM\Column(name="marketing_list_item_id", type="integer", nullable=true)
     */
    protected $marketingListItemId;

    /**
     * @var string
     *
     * @ORM\Column(name="marketing_list_item_class", type="string", unique=false, length=255, nullable=true)
     */
    protected $marketingListItemClass;

    /**
     * @var bool
     *
     * @ORM\Column(name="scheduled_for_export", type="boolean")
     */
    protected $scheduledForExport = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="scheduled_for_fields_update", type="boolean", nullable=true)
     */
    protected $scheduledForFieldsUpdate = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="new_entity", type="boolean", nullable=true)
     */
    protected $newEntity = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="entity_updated", type="boolean", nullable=true)
     */
    protected $entityUpdated = false;

    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $channel;

    /**
     * @var string Dotmailer import Id
     *
     * @ORM\Column(name="export_id", type="string", length=36, nullable=true)
     */
    protected $exportId;

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

    /**
     * @return int
     */
    public function getMarketingListItemId()
    {
        return $this->marketingListItemId;
    }

    /**
     * @param int $marketingListItemId
     *
     * @return AddressBookContact
     */
    public function setMarketingListItemId($marketingListItemId)
    {
        $this->marketingListItemId = $marketingListItemId;

        return $this;
    }

    /**
     * @return string
     */
    public function getMarketingListItemClass()
    {
        return $this->marketingListItemClass;
    }

    /**
     * @param string $marketingListItemClass
     *
     * @return AddressBookContact
     */
    public function setMarketingListItemClass($marketingListItemClass)
    {
        $this->marketingListItemClass = $marketingListItemClass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isScheduledForExport()
    {
        return $this->scheduledForExport;
    }

    /**
     * @param boolean $scheduledForExport
     *
     * @return AddressBookContact
     */
    public function setScheduledForExport($scheduledForExport)
    {
        $this->scheduledForExport = $scheduledForExport;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isScheduledForFieldsUpdate()
    {
        return $this->scheduledForFieldsUpdate;
    }

    /**
     * @param boolean $scheduledForFieldsUpdate
     * @return AddressBookContact
     */
    public function setScheduledForFieldsUpdate($scheduledForFieldsUpdate)
    {
        $this->scheduledForFieldsUpdate = $scheduledForFieldsUpdate;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNewEntity()
    {
        return $this->newEntity;
    }

    /**
     * @param boolean $newEntity
     * @return AddressBookContact
     */
    public function setNewEntity($newEntity)
    {
        $this->newEntity = $newEntity;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEntityUpdated()
    {
        return $this->entityUpdated;
    }

    /**
     * @param boolean $entityUpdated
     *
     * @return AddressBookContact
     */
    public function setEntityUpdated($entityUpdated)
    {
        $this->entityUpdated = $entityUpdated;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBookContact
     */
    public function setChannel(Channel $channel = null)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getExportId()
    {
        return $this->exportId;
    }

    /**
     * @param string $exportId
     *
     * @return AddressBookContact
     */
    public function setExportId($exportId)
    {
        $this->exportId = $exportId;

        return $this;
    }
}
