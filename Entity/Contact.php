<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;

use OroCRM\Bundle\DotmailerBundle\Model\ExtendContact;

/**
 * @ORM\Entity(repositoryClass="OroCRM\Bundle\DotmailerBundle\Entity\Repository\ContactRepository")
 * @ORM\Table(
 *      name="orocrm_dm_contact",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_contact_unq", columns={"origin_id", "channel_id"}),
 *          @ORM\UniqueConstraint(name="orocrm_dm_cnt_em_unq", columns={"email", "channel_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-user",
 *          "category"="dotmailer"
 *      },
 *      "ownership"={
 *          "owner_type"="ORGANIZATION",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="owner_id"
 *      },
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class Contact extends ExtendContact implements OriginAwareInterface, FirstNameInterface, LastNameInterface
{
    use OriginTrait;

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

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $channel;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string",length=255)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=50, nullable=true)
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=50, nullable=true)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     */
    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=6, nullable=true)
     */
    protected $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=12, nullable=true)
     */
    protected $postcode;

    /**
     * @var array
     *
     * @ORM\Column(name="merge_var_values", type="json_array", nullable=true)
     */
    protected $mergeVarValues;

    /**
     * @var Collection|AddressBookContact[]
     *
     * @ORM\OneToMany(targetEntity="AddressBookContact", mappedBy="contact", cascade={"remove"})
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     **/
    protected $addressBookContacts;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="contact", cascade={"all"})
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $activities;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="unsubscribed_date", type="datetime", nullable=true)
     */
    protected $unsubscribedDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_subscribed_date", type="datetime", nullable=true)
     */
    protected $lastSubscribedDate;

    /**
     * Initialize collections
     */
    public function __construct()
    {
        parent::__construct();
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
        $this->email = $email;

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
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     *
     * @return Contact
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     *
     * @return Contact
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     *
     * @return Contact
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * @return array
     */
    public function getMergeVarValues()
    {
        return $this->mergeVarValues;
    }
    /**
     * @param array|null $data
     *
     * @return Contact
     */
    public function setMergeVarValues(array $data = null)
    {
        $this->mergeVarValues = $data;

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
     * @param \DateTime $unsubscribedDate
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
     * @param \DateTime $lastSubscribedDate
     *
     * @return Contact
     */
    public function setLastSubscribedDate(\DateTime $lastSubscribedDate = null)
    {
        $this->lastSubscribedDate = $lastSubscribedDate;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if (!$this->updatedAt) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
