<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ActivityRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Stores dotdigital campaign activity stats.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'orocrm_dm_activity')]
#[ORM\Index(columns: ['email'], name: 'orocrm_dm_activity_email_idx')]
#[ORM\Index(columns: ['date_sent'], name: 'orocrm_dm_activity_dt_sent_idx')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_activity_unq', columns: ['campaign_id', 'contact_id', 'channel_id'])]
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
class Activity implements ChannelAwareInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    protected ?string $email = null;

    #[ORM\Column(name: 'num_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numOpens = null;

    #[ORM\Column(name: 'num_page_views', type: Types::INTEGER, nullable: true)]
    protected ?int $numPageViews = null;

    #[ORM\Column(name: 'num_clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $numClicks = null;

    #[ORM\Column(name: 'num_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numForwards = null;

    #[ORM\Column(name: 'num_estimated_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numEstimatedForwards = null;

    #[ORM\Column(name: 'num_replies', type: Types::INTEGER, nullable: true)]
    protected ?int $numReplies = null;

    #[ORM\Column(name: 'date_sent', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateSent = null;

    #[ORM\Column(name: 'date_first_opened', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateFirstOpened = null;

    #[ORM\Column(name: 'date_last_opened', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateLastOpened = null;

    #[ORM\Column(name: 'first_open_ip', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $firstOpenIp = null;

    #[ORM\Column(name: 'unsubscribed', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $unsubscribed = null;

    #[ORM\Column(name: 'soft_bounced', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $softBounced = null;

    #[ORM\Column(name: 'hard_bounced', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $hardBounced = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Organization $owner = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Campaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Contact $contact = null;

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
     * @param Channel|null $channel
     *
     * @return Activity
     */
    #[\Override]
    public function setChannel(Channel $channel = null)
    {
        $this->channel = $channel;

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
     * @param Contact|null $contact
     *
     * @return Activity
     */
    public function setContact(Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return Activity
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
     * @return int
     */
    public function getNumOpens()
    {
        return $this->numOpens;
    }

    /**
     * @param int $numOpens
     *
     * @return Activity
     */
    public function setNumOpens($numOpens)
    {
        $this->numOpens = $numOpens;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumPageViews()
    {
        return $this->numPageViews;
    }

    /**
     * @param int $numPageViews
     *
     * @return Activity
     */
    public function setNumPageViews($numPageViews)
    {
        $this->numPageViews = $numPageViews;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumClicks()
    {
        return $this->numClicks;
    }

    /**
     * @param int $numClicks
     *
     * @return Activity
     */
    public function setNumClicks($numClicks)
    {
        $this->numClicks = $numClicks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumForwards()
    {
        return $this->numForwards;
    }

    /**
     * @param int $numForwards
     *
     * @return Activity
     */
    public function setNumForwards($numForwards)
    {
        $this->numForwards = $numForwards;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumEstimatedForwards()
    {
        return $this->numEstimatedForwards;
    }

    /**
     * @param int $numEstimatedForwards
     *
     * @return Activity
     */
    public function setNumEstimatedForwards($numEstimatedForwards)
    {
        $this->numEstimatedForwards = $numEstimatedForwards;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumReplies()
    {
        return $this->numReplies;
    }

    /**
     * @param int $numReplies
     *
     * @return Activity
     */
    public function setNumReplies($numReplies)
    {
        $this->numReplies = $numReplies;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime|null $dateSent
     *
     * @return Activity
     */
    public function setDateSent(\DateTime $dateSent = null)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateFirstOpened()
    {
        return $this->dateFirstOpened;
    }

    /**
     * @param \DateTime|null $dateFirstOpened
     *
     * @return Activity
     */
    public function setDateFirstOpened(\DateTime $dateFirstOpened = null)
    {
        $this->dateFirstOpened = $dateFirstOpened;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateLastOpened()
    {
        return $this->dateLastOpened;
    }

    /**
     * @param \DateTime|null $dateLastOpened
     *
     * @return Activity
     */
    public function setDateLastOpened(\DateTime $dateLastOpened = null)
    {
        $this->dateLastOpened = $dateLastOpened;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstOpenIp()
    {
        return $this->firstOpenIp;
    }

    /**
     * @param string $firstOpenIp
     *
     * @return Activity
     */
    public function setFirstOpenIp($firstOpenIp)
    {
        $this->firstOpenIp = $firstOpenIp;

        return $this;
    }

    /**
     * @return boolean|null
     */
    public function isUnsubscribed()
    {
        return $this->unsubscribed;
    }

    /**
     * @param boolean $unsubscribed
     *
     * @return Activity
     */
    public function setUnsubscribed($unsubscribed)
    {
        $this->unsubscribed = $unsubscribed;

        return $this;
    }

    /**
     * @return boolean|null
     */
    public function isSoftBounced()
    {
        return $this->softBounced;
    }

    /**
     * @param boolean $softBounced
     *
     * @return Activity
     */
    public function setSoftBounced($softBounced)
    {
        $this->softBounced = $softBounced;

        return $this;
    }

    /**
     * @return boolean|null
     */
    public function isHardBounced()
    {
        return $this->hardBounced;
    }

    /**
     * @param boolean $hardBounced
     *
     * @return Activity
     */
    public function setHardBounced($hardBounced)
    {
        $this->hardBounced = $hardBounced;

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
     * @param \DateTime|null $createdAt
     *
     * @return Activity
     */
    public function setCreatedAt(\DateTime $createdAt = null)
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
     * @param \DateTime|null $updatedAt
     *
     * @return Activity
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
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
     * @param Organization|null $owner
     *
     * @return Activity
     */
    public function setOwner(Organization $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign|null $campaign
     *
     * @return Activity
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

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
