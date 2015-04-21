<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRM\Bundle\DotmailerBundle\Model\ExtendAddressBook;

/**
 * @ORM\Entity(repositoryClass="OroCRM\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository")
 * @ORM\Table(
 *      name="orocrm_dm_address_book",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_address_book_unq", columns={"origin_id", "channel_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-group"
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
class AddressBook extends ExtendAddressBook implements OriginAwareInterface
{
    use OriginTrait;

    /** constant for enum dm_ab_visibility */
    const VISIBILITY_PRIVATE                    = 'Private';
    const VISIBILITY_PUBLIC                     = 'Public';
    const VISIBILITY_NOTAVAILABLEINTHISVERSION  = 'NotAvailableInThisVersion';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
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
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\Campaign", mappedBy="addressBooks")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $campaigns;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\Contact", mappedBy="addressBooks")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $contacts;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(name="contact_count", type="integer", nullable=true)
     */
    protected $contactCount;

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
     * Initialize collections
     */
    public function __construct()
    {
        parent::__construct();
        $this->campaigns = new ArrayCollection();
        $this->contacts = new ArrayCollection();
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
     * @return AddressBook
     */
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
     * Get contacts collection
     *
     * @return Collection|Contact[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add specified email contact
     *
     * @param Contact $contact
     *
     * @return AddressBook
     */
    public function addContact(Contact $contact)
    {
        if (!$this->hasContact($contact)) {
            $this->getContacts()->add($contact);
            $contact->addAddressBook($this);
        }

        return $this;
    }

    /**
     * @param Contact $contact
     *
     * @return bool
     */
    public function hasContact(Contact $contact)
    {
        return $this->getContacts()->contains($contact);
    }

    /**
     * Set contacts collection
     *
     * @param Collection $contacts
     *
     * @return AddressBook
     */
    public function setContacts(Collection $contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * Remove specified contact
     *
     * @param Contact $contact
     *
     * @return AddressBook
     */
    public function removeContact(Contact $contact)
    {
        if ($this->hasContact($contact)) {
            $this->getContacts()->removeElement($contact);
            $contact->removeAddressBook($this);
        }

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
