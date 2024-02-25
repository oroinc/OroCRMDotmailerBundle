<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_Contact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Entity which represents contacts synced with dotmailer
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method AbstractEnumValue getOptInType()
 * @method Contact setOptInType(AbstractEnumValue $enumValue)
 * @method AbstractEnumValue getEmailType()
 * @method Contact setEmailType(AbstractEnumValue $enumValue)
 * @method AbstractEnumValue getStatus()
 * @method Contact setStatus(AbstractEnumValue $enumValue)
 * @mixin OroDotmailerBundle_Entity_Contact
 */
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'orocrm_dm_contact')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_contact_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\UniqueConstraint(name: 'orocrm_dm_cnt_em_unq', columns: ['email', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-user'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class Contact implements OriginAwareInterface, ExtendEntityInterface
{
    use OriginTrait;
    use ExtendEntityTrait;

    /** constant for enum dm_cnt_opt_in_type */
    const OPT_IN_TYPE_UNKNOWN                       = 'Unknown';
    const OPT_IN_TYPE_SINGLE                        = 'Single';
    const OPT_IN_TYPE_DOUBLE                        = 'Double';
    const OPT_IN_TYPE_VERIFIEDDOUBLE                = 'VerifiedDouble';
    const OPT_IN_TYPE_NOTAVAILABLEINTHISVERSION     = 'NotAvailableInThisVersion';

    /** constant for enum dm_cnt_email_type */
    const EMAIL_TYPE_PLAINTEXT                      = 'PlainText';
    const EMAIL_TYPE_HTML                           = 'Html';
    const EMAIL_TYPE_NOTAVAILABLEINTHISVERSION      = 'NotAvailableInThisVersion';

    /** constant for enum dm_cnt_status */
    const STATUS_SUBSCRIBED                         = 'Subscribed';
    const STATUS_UNSUBSCRIBED                       = 'Unsubscribed';
    const STATUS_SOFTBOUNCED                        = 'SoftBounced';
    const STATUS_HARDBOUNCED                        = 'HardBounced';
    const STATUS_ISPCOMPLAINED                      = 'IspComplained';
    const STATUS_MAILBLOCKED                        = 'MailBlocked';
    const STATUS_PENDINGOPTIN                       = 'PendingOptIn';
    const STATUS_DIRECTCOMPLAINT                    = 'DirectComplaint';
    const STATUS_DELETED                            = 'Deleted';
    const STATUS_SHAREDSUPPRESSION                  = 'SharedSuppression';
    const STATUS_SUPPRESSED                         = 'Suppressed';
    const STATUS_NOTALLOWED                         = 'NotAllowed';
    const STATUS_DOMAINSUPPRESSION                  = 'DomainSuppression';
    const STATUS_NOMXRECORD                         = 'NoMxRecord';
    const STATUS_NOTAVAILABLEINTHISVERSION          = 'NotAvailableInThisVersion';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $email = null;

    /**
     * @var Collection<int, AddressBookContact>
     **/
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: AddressBookContact::class, cascade: ['remove'])]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $addressBookContacts = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'data_fields', type: 'json_array', nullable: true)]
    protected $dataFields;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Activity::class, cascade: ['all'])]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $activities = null;

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

    #[ORM\Column(name: 'unsubscribed_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $unsubscribedDate = null;

    #[ORM\Column(name: 'last_subscribed_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastSubscribedDate = null;

    /**
     * Initialize collections
     */
    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->addressBookContacts = new ArrayCollection();
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
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     *
     * @return Contact
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $this->email = strtolower($email);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * @return Contact
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return Contact
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * @return Contact
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

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
     * @param Collection|AddressBookContact[] $addressBookContacts
     *
     * @return Contact
     */
    public function setAddressBookContacts($addressBookContacts)
    {
        $this->addressBookContacts = $addressBookContacts;

        return $this;
    }

    /**
     * @param AddressBookContact $addressBookContact
     *
     * @return Contact
     */
    public function addAddressBookContact(AddressBookContact $addressBookContact)
    {
        if (!$this->addressBookContacts->contains($addressBookContact)) {
            $addressBookContact->setContact($this);
            $this->addressBookContacts->add($addressBookContact);
        }

        return $this;
    }

    /**
     * @param AddressBookContact $addressBookContact
     *
     * @return Contact
     */
    public function removeAddressBookContact(AddressBookContact $addressBookContact)
    {
        if ($this->addressBookContacts->contains($addressBookContact)) {
            $this->addressBookContacts->removeElement($addressBookContact);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getDataFields()
    {
        return $this->dataFields;
    }

    /**
     * @param array $dataFields
     * @return Contact
     */
    public function setDataFields($dataFields)
    {
        $this->dataFields = $dataFields;

        return $this;
    }

    /**
     * @return Collection|Activity[]
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param Collection|Activity[] $activities
     *
     * @return Contact
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @param Activity $activity
     *
     * @return Contact
     */
    public function addActivity(Activity $activity)
    {
        if (!$this->getActivities()->contains($activity)) {
            $this->getActivities()->add($activity);
            $activity->setContact($this);
        }

        return $this;
    }

    /**
     * @param Activity $activity
     *
     * @return Contact
     */
    public function removeActivity(Activity $activity)
    {
        if ($this->getActivities()->contains($activity)) {
            $this->getActivities()->removeElement($activity);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasActivities()
    {
        return !$this->getActivities()->isEmpty();
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
     * @return Contact
     */
    public function setUnsubscribedDate(\DateTime $unsubscribedDate = null)
    {
        $this->unsubscribedDate = $unsubscribedDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSubscribedDate()
    {
        return $this->lastSubscribedDate;
    }

    /**
     * @param \DateTime|null $lastSubscribedDate
     *
     * @return Contact
     */
    public function setLastSubscribedDate(\DateTime $lastSubscribedDate = null)
    {
        $this->lastSubscribedDate = $lastSubscribedDate;

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
