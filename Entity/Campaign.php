<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRM\Bundle\DotmailerBundle\Model\ExtendCampaign;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="orocrm_dm_campaign",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_campaign_unq", columns={"origin_id", "channel_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-envelope"
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
class Campaign extends ExtendCampaign implements OriginAwareInterface
{
    use OriginTrait;

    /** constant for enum orocrm_dm_cmp_reply_action */
    const REPLY_ACTION_UNSET                        = 'Unset';
    const REPLY_ACTION_WEBMAILFORWARD               = 'WebMailForward';
    const REPLY_ACTION_WEBMAIL                      = 'Webmail';
    const REPLY_ACTION_DELETE                       = 'Delete';
    const REPLY_ACTION_NOTAVAILABLEINTHISVERSION    = 'NotAvailableInThisVersion';

    /** constant for enum orocrm_dm_cmp_status */
    const STATUS_UNSENT                             = 'Unsent';
    const STATUS_SENDING                            = 'Sending';
    const STATUS_SENT                               = 'Sent';
    const STATUS_PAUSED                             = 'Paused';
    const STATUS_CANCELLED                          = 'Cancelled';
    const STATUS_REQUIRESSYSTEMAPPROVAL             = 'RequiresSystemApproval';
    const STATUS_REQUIRESSMSAPPROVAL                = 'RequiresSMSApproval';
    const STATUS_REQUIRESWORKFLOWAPPROVAL           = 'RequiresWorkflowApproval';
    const STATUS_TRIGGERED                          = 'Triggered';
    const STATUS_NOTAVAILABLEINTHISVERSION          = 'NotAvailableInThisVersion';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    protected $fromName;

    /**
     * @var string
     *
     * @ORM\Column(name="from_address", type="string", length=255, nullable=true)
     */
    protected $fromAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="html_content", type="text", nullable=true)
     */
    protected $htmlContent;

    /**
     * @var string
     *
     * @ORM\Column(name="plain_text_content", type="text", nullable=true)
     */
    protected $plainTextContent;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_to_address", type="string", length=255, nullable=true)
     */
    protected $replyToAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_split_test", type="boolean", nullable=true)
     */
    protected $isSplitTest;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     *
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
     *
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
     *
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
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\AddressBook")
     * @ORM\JoinTable(name="orocrm_dm_campaign_to_ab",
     *      joinColumns={@ORM\JoinColumn(name="campaign_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="address_book_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $addressBooks;

    /**
     * Initialize collections
     */
    public function __construct()
    {
        parent::__construct();
        $this->addressBooks = new ArrayCollection();
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
     * @return Campaign
     */
    public function setChannel($channel)
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
     * @return Campaign
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return Campaign
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
     * @return Campaign
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
     * @return Campaign
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @param string $fromAddress
     *
     * @return Campaign
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     *
     * @return Campaign
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsSplitTest()
    {
        return $this->isSplitTest;
    }

    /**
     * @param boolean $isSplitTest
     *
     * @return Campaign
     */
    public function setIsSplitTest($isSplitTest)
    {
        $this->isSplitTest = $isSplitTest;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return Campaign
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlContent()
    {
        return $this->htmlContent;
    }

    /**
     * @param string $htmlContent
     *
     * @return Campaign
     */
    public function setHtmlContent($htmlContent)
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainTextContent()
    {
        return $this->plainTextContent;
    }

    /**
     * @param string $plainTextContent
     *
     * @return Campaign
     */
    public function setPlainTextContent($plainTextContent)
    {
        $this->plainTextContent = $plainTextContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyToAddress()
    {
        return $this->replyToAddress;
    }

    /**
     * @param string $replyToAddress
     *
     * @return Campaign
     */
    public function setReplyToAddress($replyToAddress)
    {
        $this->replyToAddress = $replyToAddress;

        return $this;
    }

    /**
     * Set address books.
     *
     * @param Collection|AddressBook[] $addressBooks
     *
     * @return Campaign
     */
    public function resetAddressBooks($addressBooks)
    {
        $this->getAddressBooks()->clear();

        foreach ($addressBooks as $addressBook) {
            $this->addAddressBook($addressBook);
        }

        return $this;
    }

    /**
     * Get address books.
     *
     * @return Collection|AddressBook[]
     */
    public function getAddressBooks()
    {
        return $this->addressBooks;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return Campaign
     */
    public function addAddressBook(AddressBook $addressBook)
    {
        if (!$this->getAddressBooks()->contains($addressBook)) {
            $this->getAddressBooks()->add($addressBook);
            $addressBook->addCampaign($this);
        }

        return $this;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return Campaign
     */
    public function removeAddressBook(AddressBook $addressBook)
    {
        if ($this->getAddressBooks()->contains($addressBook)) {
            $this->getAddressBooks()->removeElement($addressBook);
            $addressBook->removeCampaign($this);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAddressBooks()
    {
        return !$this->getAddressBooks()->isEmpty();
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
