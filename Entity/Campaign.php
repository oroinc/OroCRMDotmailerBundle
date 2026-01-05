<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_Campaign;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\Repository\CampaignRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Represents a dotdigital campaign.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method EnumOptionInterface getReplyAction()
 * @method Campaign setReplyAction(EnumOptionInterface $enumOption)
 * @method EnumOptionInterface getStatus()
 * @method Campaign setStatus(EnumOptionInterface $enumOption)
 * @mixin OroDotmailerBundle_Entity_Campaign
 */
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'orocrm_dm_campaign')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_campaign_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-envelope'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class Campaign implements OriginAwareInterface, ExtendEntityInterface
{
    use OriginTrait;
    use ExtendEntityTrait;

    /** constant for enum dm_cmp_reply_action */
    public const REPLY_ACTION_UNSET                        = 'Unset';
    public const REPLY_ACTION_WEBMAILFORWARD               = 'WebMailForward';
    public const REPLY_ACTION_WEBMAIL                      = 'Webmail';
    public const REPLY_ACTION_DELETE                       = 'Delete';
    public const REPLY_ACTION_NOTAVAILABLEINTHISVERSION    = 'NotAvailableInThisVersion';

    /** constant for enum dm_cmp_status */
    public const STATUS_UNSENT                             = 'Unsent';
    public const STATUS_SENDING                            = 'Sending';
    public const STATUS_SENT                               = 'Sent';
    public const STATUS_PAUSED                             = 'Paused';
    public const STATUS_CANCELLED                          = 'Cancelled';
    public const STATUS_REQUIRESSYSTEMAPPROVAL             = 'RequiresSystemApproval';
    public const STATUS_REQUIRESSMSAPPROVAL                = 'RequiresSMSApproval';
    public const STATUS_REQUIRESWORKFLOWAPPROVAL           = 'RequiresWorkflowApproval';
    public const STATUS_TRIGGERED                          = 'Triggered';
    public const STATUS_NOTAVAILABLEINTHISVERSION          = 'NotAvailableInThisVersion';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'from_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fromName = null;

    #[ORM\Column(name: 'from_address', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fromAddress = null;

    #[ORM\Column(name: 'html_content', type: Types::TEXT, nullable: true)]
    protected ?string $htmlContent = null;

    #[ORM\Column(name: 'plain_text_content', type: Types::TEXT, nullable: true)]
    protected ?string $plainTextContent = null;

    #[ORM\Column(name: 'reply_to_address', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $replyToAddress = null;

    #[ORM\Column(name: 'is_split_test', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $isSplitTest = null;

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
     * @var Collection<int, AddressBook>
     */
    #[ORM\ManyToMany(targetEntity: AddressBook::class, inversedBy: 'campaigns')]
    #[ORM\JoinTable(name: 'orocrm_dm_campaign_to_ab')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'address_book_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $addressBooks = null;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Activity::class, cascade: ['all'])]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $activities = null;

    #[ORM\OneToOne(targetEntity: EmailCampaign::class)]
    #[ORM\JoinColumn(name: 'email_campaign_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?EmailCampaign $emailCampaign = null;

    #[ORM\OneToOne(mappedBy: 'campaign', targetEntity: CampaignSummary::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'campaign_summary_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?CampaignSummary $campaignSummary = null;

    #[ORM\Column(name: 'is_deleted', type: Types::BOOLEAN)]
    protected ?bool $deleted = false;

    /**
     * Initialize collections
     */
    public function __construct()
    {
        $this->addressBooks = new ArrayCollection();
        $this->activities = new ArrayCollection();
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
     * @return Campaign
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
     *
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
     *
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
     *
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
    public function setAddressBooks($addressBooks)
    {
        $this->addressBooks = $addressBooks;

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
     * @return Collection|Activity[]
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param Collection|Activity[] $activities
     *
     * @return Campaign
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @param Activity $activity
     *
     * @return Campaign
     */
    public function addActivity(Activity $activity)
    {
        if (!$this->getActivities()->contains($activity)) {
            $this->getActivities()->add($activity);
            $activity->setCampaign($this);
        }

        return $this;
    }

    /**
     * @param Activity $activity
     *
     * @return Campaign
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
     * @return EmailCampaign
     */
    public function getEmailCampaign()
    {
        return $this->emailCampaign;
    }

    /**
     * @param EmailCampaign|null $emailCampaign
     *
     * @return Campaign
     */
    public function setEmailCampaign(?EmailCampaign $emailCampaign = null)
    {
        $this->emailCampaign = $emailCampaign;

        return $this;
    }

    /**
     * @return CampaignSummary
     */
    public function getCampaignSummary()
    {
        return $this->campaignSummary;
    }

    /**
     * @param CampaignSummary|null $campaignSummary
     *
     * @return Campaign
     */
    public function setCampaignSummary(?CampaignSummary $campaignSummary = null)
    {
        $this->campaignSummary = $campaignSummary;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return Campaign
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

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
