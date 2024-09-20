<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Address book contact entity.
 *
 * @method EnumOptionInterface getStatus()
 * @method AddressBookContact setStatus(EnumOptionInterface $enumOption)
 * @method EnumOptionInterface getExportOperationType()
 * @method AddressBookContact setExportOperationType(EnumOptionInterface $enumOption)
 * @mixin OroDotmailerBundle_Entity_AddressBookContact
 */
#[ORM\Entity(repositoryClass: AddressBookContactRepository::class)]
#[ORM\Table(name: 'orocrm_dm_ab_contact')]
#[ORM\Index(columns: ['export_id'], name: 'orocrm_dm_ab_cnt_export_id_idx')]
#[ORM\Index(
    columns: ['marketing_list_item_class', 'marketing_list_item_id'],
    name: 'IDX_MARKETING_LIST_ITEM_CLASS_ID'
)]
#[ORM\UniqueConstraint(name: 'orocrm_dm_ab_cnt_unq', columns: ['address_book_id', 'contact_id'])]
#[Config]
class AddressBookContact implements ChannelAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    const EXPORT_NEW_CONTACT = 'new';
    const EXPORT_ADD_TO_ADDRESS_BOOK = 'add';
    const EXPORT_UPDATE_CONTACT = 'update';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AddressBook::class, inversedBy: 'addressBookContacts')]
    #[ORM\JoinColumn(name: 'address_book_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AddressBook $addressBook = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'addressBookContacts')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Contact $contact = null;

    #[ORM\Column(name: 'unsubscribed_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $unsubscribedDate = null;

    #[ORM\Column(name: 'marketing_list_item_id', type: Types::INTEGER, nullable: true)]
    protected ?int $marketingListItemId = null;

    #[ORM\Column(name: 'marketing_list_item_class', type: Types::STRING, length: 255, unique: false, nullable: true)]
    protected ?string $marketingListItemClass = null;

    #[ORM\Column(name: 'scheduled_for_export', type: Types::BOOLEAN)]
    protected ?bool $scheduledForExport = false;

    #[ORM\Column(name: 'scheduled_for_fields_update', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $scheduledForFieldsUpdate = false;

    #[ORM\Column(name: 'new_entity', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $newEntity = false;

    #[ORM\Column(name: 'entity_updated', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $entityUpdated = false;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Channel $channel = null;

    /**
     * @var string|null Dotmailer import Id
     */
    #[ORM\Column(name: 'export_id', type: Types::STRING, length: 36, nullable: true)]
    protected ?string $exportId = null;

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
     * @param \DateTime|null $unsubscribedDate
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
     * @param Channel|null $channel
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
