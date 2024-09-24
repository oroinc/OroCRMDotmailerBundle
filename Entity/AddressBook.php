<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Address book entity.
 *
 * @method EnumOptionInterface getVisibility()
 * @method AddressBook setVisibility(EnumOptionInterface $enumOption)
 * @method EnumOptionInterface getSyncStatus()
 * @method AddressBook setSyncStatus(EnumOptionInterface $enumOption)
 * @mixin OroDotmailerBundle_Entity_AddressBook
 */
#[ORM\Entity(repositoryClass: AddressBookRepository::class)]
#[ORM\Table(name: 'orocrm_dm_address_book')]
#[ORM\Index(columns: ['last_imported_at'], name: 'orocrm_dm_ab_imported_at_idx')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_address_book_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-users'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class AddressBook implements OriginAwareInterface, ExtendEntityInterface
{
    use OriginTrait;
    use ExtendEntityTrait;

    /** constant for enum dm_ab_visibility */
    const VISIBILITY_PRIVATE                    = 'Private';
    const VISIBILITY_PUBLIC                     = 'Public';
    const VISIBILITY_NOTAVAILABLEINTHISVERSION  = 'NotAvailableInThisVersion';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => false]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\ManyToMany(targetEntity: Campaign::class, mappedBy: 'addressBooks')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $campaigns = null;

    /**
     * @var Collection<int, AddressBookContact>
     */
    #[ORM\OneToMany(mappedBy: 'addressBook', targetEntity: AddressBookContact::class, cascade: ['remove'])]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $addressBookContacts = null;

    #[ORM\OneToOne(targetEntity: MarketingList::class)]
    #[ORM\JoinColumn(name: 'marketing_list_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?MarketingList $marketingList = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'contact_count', type: Types::INTEGER, nullable: true)]
    protected ?int $contactCount = null;

    #[ORM\Column(name: 'last_exported_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?\DateTimeInterface $lastExportedAt = null;

    #[ORM\Column(name: 'last_imported_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?\DateTimeInterface $lastImportedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Organization $owner = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, AddressBookContactsExport>
     */
    #[ORM\OneToMany(mappedBy: 'addressBook', targetEntity: AddressBookContactsExport::class)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $addressBookContactsExports = null;

    /**
     * Controls whether we need to create new entities when importing contacts from dotmailer
     *
     * @var boolean
     */
    #[ORM\Column(name: 'create_entities', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $createEntities = false;

    /**
     * Initialize collections
     */
    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
        $this->addressBookContacts = new ArrayCollection();
        $this->addressBookContactsExports = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Channel
     */
    #[\Override]
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBook
     */
    #[\Override]
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return AddressBook
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactCount()
    {
        return $this->contactCount;
    }

    /**
     * @param int $contactCount
     *
     * @return AddressBook
     */
    public function setContactCount($contactCount)
    {
        $this->contactCount = $contactCount;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastExportedAt()
    {
        return $this->lastExportedAt;
    }

    /**
     * @param \DateTime|null $lastExportedAt
     *
     * @return AddressBook
     */
    public function setLastExportedAt(\DateTime $lastExportedAt = null)
    {
        $this->lastExportedAt = $lastExportedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastImportedAt()
    {
        return $this->lastImportedAt;
    }

    /**
     * @param \DateTime|null $lastImportedAt
     *
     * @return AddressBook
     */
    public function setLastImportedAt(\DateTime $lastImportedAt = null)
    {
        $this->lastImportedAt = $lastImportedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return AddressBook
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $owner
     *
     * @return AddressBook
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return AddressBook
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get email campaign collection
     *
     * @return Collection|Campaign[]
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * Add specified email campaign
     *
     * @param Campaign $campaign
     *
     * @return AddressBook
     */
    public function addCampaign(Campaign $campaign)
    {
        if (!$this->hasCampaign($campaign)) {
            $this->getCampaigns()->add($campaign);
            $campaign->addAddressBook($this);
        }

        return $this;
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function hasCampaign(Campaign $campaign)
    {
        return $this->getCampaigns()->contains($campaign);
    }

    /**
     * Set email campaigns collection
     *
     * @param Collection $campaigns
     *
     * @return AddressBook
     */
    public function setCampaigns(Collection $campaigns)
    {
        $this->campaigns = $campaigns;

        return $this;
    }

    /**
     * Remove specified email campaign
     *
     * @param Campaign $campaign
     *
     * @return AddressBook
     */
    public function removeCampaign(Campaign $campaign)
    {
        if ($this->hasCampaign($campaign)) {
            $this->getCampaigns()->removeElement($campaign);
            $campaign->removeAddressBook($this);
        }

        return $this;
    }

    /**
     * @return MarketingList
     */
    public function getMarketingList()
    {
        return $this->marketingList;
    }

    /**
     * @param MarketingList|null $marketingList
     *
     * @return AddressBook
     */
    public function setMarketingList(MarketingList $marketingList = null)
    {
        $this->marketingList = $marketingList;

        return $this;
    }

    /**
     * @return Collection|AddressBookContact[]
     */
    public function getAddressBookContacts()
    {
        return $this->addressBookContacts;
    }

    /**
     * @param Collection $addressBookContacts
     *
     * @return AddressBook
     */
    public function setAddressBookContacts($addressBookContacts)
    {
        $this->addressBookContacts = $addressBookContacts;

        return $this;
    }

    /**
     * @param AddressBookContact $addressBookContact
     *
     * @return AddressBook
     */
    public function addAddressBookContact(AddressBookContact $addressBookContact)
    {
        if (!$this->addressBookContacts->contains($addressBookContact)) {
            $addressBookContact->setAddressBook($this);
            $this->addressBookContacts->add($addressBookContact);
        }

        return $this;
    }

    /**
     * @param AddressBookContact $addressBookContact
     *
     * @return AddressBook
     */
    public function removeAddressBookContact(AddressBookContact $addressBookContact)
    {
        if ($this->addressBookContacts->contains($addressBookContact)) {
            $this->addressBookContacts->removeElement($addressBookContact);
        }

        return $this;
    }

    /**
     * @return AddressBookContactsExport[]|Collection
     */
    public function getAddressBookContactsExports()
    {
        return $this->addressBookContactsExports;
    }

    /**
     * @param AddressBookContactsExport[] $addressBookContactsExports
     *
     * @return AddressBook
     */
    public function setAddressBookContactsExports($addressBookContactsExports)
    {
        $this->addressBookContactsExports = $addressBookContactsExports;

        return $this;
    }

    /**
     * @param AddressBookContactsExport $addressBookContactsExport
     *
     * @return AddressBook
     */
    public function addAddressBookContactsExport(AddressBookContactsExport $addressBookContactsExport)
    {
        if (!$this->addressBookContactsExports->contains($addressBookContactsExport)) {
            $addressBookContactsExport->setAddressBook($this);
            $this->addressBookContactsExports->add($addressBookContactsExport);
        }

        return $this;
    }

    /**
     * @param AddressBookContactsExport $addressBookContactsExport
     *
     * @return AddressBook
     */
    public function removeAddressBookContactsExport(AddressBookContactsExport $addressBookContactsExport)
    {
        if ($this->addressBookContactsExports->contains($addressBookContactsExport)) {
            $this->addressBookContactsExports->removeElement($addressBookContactsExport);
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCreateEntities()
    {
        return $this->createEntities;
    }

    /**
     * @param boolean $createEntities
     *
     * @return AddressBook
     */
    public function setCreateEntities($createEntities)
    {
        $this->createEntities = $createEntities;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if (!$this->updatedAt) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
