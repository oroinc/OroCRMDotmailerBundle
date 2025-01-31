<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DotmailerBundle\Entity\Repository\CampaignSummaryRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Dotmailer Campaign Summary entity
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[ORM\Entity(repositoryClass: CampaignSummaryRepository::class)]
#[ORM\Table(name: 'orocrm_dm_campaign_summary')]
#[ORM\Index(columns: ['date_sent'], name: 'orocrm_dm_camp_sum_dt_sent_idx')]
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
class CampaignSummary implements ChannelAwareInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\OneToOne(inversedBy: 'campaignSummary', targetEntity: Campaign::class)]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Campaign $campaign = null;

    #[ORM\Column(name: 'date_sent', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateSent = null;

    #[ORM\Column(name: 'num_unique_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numUniqueOpens = null;

    #[ORM\Column(name: 'num_unique_text_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numUniqueTextOpens = null;

    #[ORM\Column(name: 'num_total_unique_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalUniqueOpens = null;

    #[ORM\Column(name: 'num_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numOpens = null;

    #[ORM\Column(name: 'num_text_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextOpens = null;

    #[ORM\Column(name: 'num_total_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalOpens = null;

    #[ORM\Column(name: 'num_clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $numClicks = null;

    #[ORM\Column(name: 'num_text_clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextClicks = null;

    #[ORM\Column(name: 'num_total_clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalClicks = null;

    #[ORM\Column(name: 'num_page_views', type: Types::INTEGER, nullable: true)]
    protected ?int $numPageViews = null;

    #[ORM\Column(name: 'num_total_page_views', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalPageViews = null;

    #[ORM\Column(name: 'num_text_page_views', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextPageViews = null;

    #[ORM\Column(name: 'num_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numForwards = null;

    #[ORM\Column(name: 'num_text_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextForwards = null;

    #[ORM\Column(name: 'num_estimated_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numEstimatedForwards = null;

    #[ORM\Column(name: 'num_text_estimated_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextEstimatedForwards = null;

    #[ORM\Column(name: 'num_total_estimated_forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalEstimatedForwards = null;

    #[ORM\Column(name: 'num_replies', type: Types::INTEGER, nullable: true)]
    protected ?int $numReplies = null;

    #[ORM\Column(name: 'num_text_replies', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextReplies = null;

    #[ORM\Column(name: 'num_total_replies', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalReplies = null;

    #[ORM\Column(name: 'num_hard_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numHardBounces = null;

    #[ORM\Column(name: 'num_text_hard_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextHardBounces = null;

    #[ORM\Column(name: 'num_total_hard_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalHardBounces = null;

    #[ORM\Column(name: 'num_soft_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numSoftBounces = null;

    #[ORM\Column(name: 'num_text_soft_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextSoftBounces = null;

    #[ORM\Column(name: 'num_total_soft_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalSoftBounces = null;

    #[ORM\Column(name: 'num_unsubscribes', type: Types::INTEGER, nullable: true)]
    protected ?int $numUnsubscribes = null;

    #[ORM\Column(name: 'num_text_unsubscribes', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextUnsubscribes = null;

    #[ORM\Column(name: 'num_total_unsubscribes', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalUnsubscribes = null;

    #[ORM\Column(name: 'num_isp_complaints', type: Types::INTEGER, nullable: true)]
    protected ?int $numIspComplaints = null;

    #[ORM\Column(name: 'num_text_isp_complaints', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextIspComplaints = null;

    #[ORM\Column(name: 'num_total_isp_complaints', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalIspComplaints = null;

    #[ORM\Column(name: 'num_mail_blocks', type: Types::INTEGER, nullable: true)]
    protected ?int $numMailBlocks = null;

    #[ORM\Column(name: 'num_text_mail_blocks', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextMailBlocks = null;

    #[ORM\Column(name: 'num_total_mail_blocks', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalMailBlocks = null;

    #[ORM\Column(name: 'num_sent', type: Types::INTEGER, nullable: true)]
    protected ?int $numSent = null;

    #[ORM\Column(name: 'num_text_sent', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextSent = null;

    #[ORM\Column(name: 'num_total_sent', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalSent = null;

    #[ORM\Column(name: 'num_recipients_clicked', type: Types::INTEGER, nullable: true)]
    protected ?int $numRecipientsClicked = null;

    #[ORM\Column(name: 'num_delivered', type: Types::INTEGER, nullable: true)]
    protected ?int $numDelivered = null;

    #[ORM\Column(name: 'num_text_delivered', type: Types::INTEGER, nullable: true)]
    protected ?int $numTextDelivered = null;

    #[ORM\Column(name: 'num_total_delivered', type: Types::INTEGER, nullable: true)]
    protected ?int $numTotalDelivered = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_delivered', type: Types::FLOAT, nullable: true)]
    protected $percentageDelivered;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_unique_opens', type: Types::FLOAT, nullable: true)]
    protected $percentageUniqueOpens;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_opens', type: Types::FLOAT, nullable: true)]
    protected $percentageOpens;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_unsubscribes', type: Types::FLOAT, nullable: true)]
    protected $percentageUnsubscribes;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_replies', type: Types::FLOAT, nullable: true)]
    protected $percentageReplies;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_hard_bounces', type: Types::FLOAT, nullable: true)]
    protected $percentageHardBounces;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_soft_bounces', type: Types::FLOAT, nullable: true)]
    protected $percentageSoftBounces;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_users_clicked', type: Types::FLOAT, nullable: true)]
    protected $percentageUsersClicked;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'percentage_clicks_to_opens', type: Types::FLOAT, nullable: true)]
    protected $percentageClicksToOpens;

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
     * @return CampaignSummary
     */
    #[\Override]
    public function setChannel(?Channel $channel = null)
    {
        $this->channel = $channel;

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
     * @param \DateTime $dateSent
     *
     * @return CampaignSummary
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumUniqueOpens()
    {
        return $this->numUniqueOpens;
    }

    /**
     * @param int $numUniqueOpens
     *
     * @return CampaignSummary
     */
    public function setNumUniqueOpens($numUniqueOpens)
    {
        $this->numUniqueOpens = $numUniqueOpens;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumUniqueTextOpens()
    {
        return $this->numUniqueTextOpens;
    }

    /**
     * @param int $numUniqueTextOpens
     *
     * @return CampaignSummary
     */
    public function setNumUniqueTextOpens($numUniqueTextOpens)
    {
        $this->numUniqueTextOpens = $numUniqueTextOpens;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalUniqueOpens()
    {
        return $this->numTotalUniqueOpens;
    }

    /**
     * @param int $numTotalUniqueOpens
     *
     * @return CampaignSummary
     */
    public function setNumTotalUniqueOpens($numTotalUniqueOpens)
    {
        $this->numTotalUniqueOpens = $numTotalUniqueOpens;

        return $this;
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
     * @return CampaignSummary
     */
    public function setNumOpens($numOpens)
    {
        $this->numOpens = $numOpens;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextOpens()
    {
        return $this->numTextOpens;
    }

    /**
     * @param int $numTextOpens
     *
     * @return CampaignSummary
     */
    public function setNumTextOpens($numTextOpens)
    {
        $this->numTextOpens = $numTextOpens;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalOpens()
    {
        return $this->numTotalOpens;
    }

    /**
     * @param int $numTotalOpens
     *
     * @return CampaignSummary
     */
    public function setNumTotalOpens($numTotalOpens)
    {
        $this->numTotalOpens = $numTotalOpens;

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
     * @return CampaignSummary
     */
    public function setNumClicks($numClicks)
    {
        $this->numClicks = $numClicks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextClicks()
    {
        return $this->numTextClicks;
    }

    /**
     * @param int $numTextClicks
     *
     * @return CampaignSummary
     */
    public function setNumTextClicks($numTextClicks)
    {
        $this->numTextClicks = $numTextClicks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalClicks()
    {
        return $this->numTotalClicks;
    }

    /**
     * @param int $numTotalClicks
     *
     * @return CampaignSummary
     */
    public function setNumTotalClicks($numTotalClicks)
    {
        $this->numTotalClicks = $numTotalClicks;

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
     * @return CampaignSummary
     */
    public function setNumPageViews($numPageViews)
    {
        $this->numPageViews = $numPageViews;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalPageViews()
    {
        return $this->numTotalPageViews;
    }

    /**
     * @param int $numTotalPageViews
     *
     * @return CampaignSummary
     */
    public function setNumTotalPageViews($numTotalPageViews)
    {
        $this->numTotalPageViews = $numTotalPageViews;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextPageViews()
    {
        return $this->numTextPageViews;
    }

    /**
     * @param int $numTextPageViews
     *
     * @return CampaignSummary
     */
    public function setNumTextPageViews($numTextPageViews)
    {
        $this->numTextPageViews = $numTextPageViews;

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
     * @return CampaignSummary
     */
    public function setNumForwards($numForwards)
    {
        $this->numForwards = $numForwards;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextForwards()
    {
        return $this->numTextForwards;
    }

    /**
     * @param int $numTextForwards
     *
     * @return CampaignSummary
     */
    public function setNumTextForwards($numTextForwards)
    {
        $this->numTextForwards = $numTextForwards;

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
     * @return CampaignSummary
     */
    public function setNumEstimatedForwards($numEstimatedForwards)
    {
        $this->numEstimatedForwards = $numEstimatedForwards;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextEstimatedForwards()
    {
        return $this->numTextEstimatedForwards;
    }

    /**
     * @param int $numTextEstimatedForwards
     *
     * @return CampaignSummary
     */
    public function setNumTextEstimatedForwards($numTextEstimatedForwards)
    {
        $this->numTextEstimatedForwards = $numTextEstimatedForwards;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalEstimatedForwards()
    {
        return $this->numTotalEstimatedForwards;
    }

    /**
     * @param int $numTotalEstimatedForwards
     *
     * @return CampaignSummary
     */
    public function setNumTotalEstimatedForwards($numTotalEstimatedForwards)
    {
        $this->numTotalEstimatedForwards = $numTotalEstimatedForwards;

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
     * @return CampaignSummary
     */
    public function setNumReplies($numReplies)
    {
        $this->numReplies = $numReplies;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextReplies()
    {
        return $this->numTextReplies;
    }

    /**
     * @param int $numTextReplies
     *
     * @return CampaignSummary
     */
    public function setNumTextReplies($numTextReplies)
    {
        $this->numTextReplies = $numTextReplies;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalReplies()
    {
        return $this->numTotalReplies;
    }

    /**
     * @param int $numTotalReplies
     *
     * @return CampaignSummary
     */
    public function setNumTotalReplies($numTotalReplies)
    {
        $this->numTotalReplies = $numTotalReplies;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumHardBounces()
    {
        return $this->numHardBounces;
    }

    /**
     * @param int $numHardBounces
     *
     * @return CampaignSummary
     */
    public function setNumHardBounces($numHardBounces)
    {
        $this->numHardBounces = $numHardBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextHardBounces()
    {
        return $this->numTextHardBounces;
    }

    /**
     * @param int $numTextHardBounces
     *
     * @return CampaignSummary
     */
    public function setNumTextHardBounces($numTextHardBounces)
    {
        $this->numTextHardBounces = $numTextHardBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalHardBounces()
    {
        return $this->numTotalHardBounces;
    }

    /**
     * @param int $numTotalHardBounces
     *
     * @return CampaignSummary
     */
    public function setNumTotalHardBounces($numTotalHardBounces)
    {
        $this->numTotalHardBounces = $numTotalHardBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumSoftBounces()
    {
        return $this->numSoftBounces;
    }

    /**
     * @param int $numSoftBounces
     *
     * @return CampaignSummary
     */
    public function setNumSoftBounces($numSoftBounces)
    {
        $this->numSoftBounces = $numSoftBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextSoftBounces()
    {
        return $this->numTextSoftBounces;
    }

    /**
     * @param int $numTextSoftBounces
     *
     * @return CampaignSummary
     */
    public function setNumTextSoftBounces($numTextSoftBounces)
    {
        $this->numTextSoftBounces = $numTextSoftBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalSoftBounces()
    {
        return $this->numTotalSoftBounces;
    }

    /**
     * @param int $numTotalSoftBounces
     *
     * @return CampaignSummary
     */
    public function setNumTotalSoftBounces($numTotalSoftBounces)
    {
        $this->numTotalSoftBounces = $numTotalSoftBounces;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumUnsubscribes()
    {
        return $this->numUnsubscribes;
    }

    /**
     * @param int $numUnsubscribes
     *
     * @return CampaignSummary
     */
    public function setNumUnsubscribes($numUnsubscribes)
    {
        $this->numUnsubscribes = $numUnsubscribes;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextUnsubscribes()
    {
        return $this->numTextUnsubscribes;
    }

    /**
     * @param int $numTextUnsubscribes
     *
     * @return CampaignSummary
     */
    public function setNumTextUnsubscribes($numTextUnsubscribes)
    {
        $this->numTextUnsubscribes = $numTextUnsubscribes;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalUnsubscribes()
    {
        return $this->numTotalUnsubscribes;
    }

    /**
     * @param int $numTotalUnsubscribes
     *
     * @return CampaignSummary
     */
    public function setNumTotalUnsubscribes($numTotalUnsubscribes)
    {
        $this->numTotalUnsubscribes = $numTotalUnsubscribes;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumIspComplaints()
    {
        return $this->numIspComplaints;
    }

    /**
     * @param int $numIspComplaints
     *
     * @return CampaignSummary
     */
    public function setNumIspComplaints($numIspComplaints)
    {
        $this->numIspComplaints = $numIspComplaints;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextIspComplaints()
    {
        return $this->numTextIspComplaints;
    }

    /**
     * @param int $numTextIspComplaints
     *
     * @return CampaignSummary
     */
    public function setNumTextIspComplaints($numTextIspComplaints)
    {
        $this->numTextIspComplaints = $numTextIspComplaints;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalIspComplaints()
    {
        return $this->numTotalIspComplaints;
    }

    /**
     * @param int $numTotalIspComplaints
     *
     * @return CampaignSummary
     */
    public function setNumTotalIspComplaints($numTotalIspComplaints)
    {
        $this->numTotalIspComplaints = $numTotalIspComplaints;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumMailBlocks()
    {
        return $this->numMailBlocks;
    }

    /**
     * @param int $numMailBlocks
     *
     * @return CampaignSummary
     */
    public function setNumMailBlocks($numMailBlocks)
    {
        $this->numMailBlocks = $numMailBlocks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextMailBlocks()
    {
        return $this->numTextMailBlocks;
    }

    /**
     * @param int $numTextMailBlocks
     *
     * @return CampaignSummary
     */
    public function setNumTextMailBlocks($numTextMailBlocks)
    {
        $this->numTextMailBlocks = $numTextMailBlocks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalMailBlocks()
    {
        return $this->numTotalMailBlocks;
    }

    /**
     * @param int $numTotalMailBlocks
     *
     * @return CampaignSummary
     */
    public function setNumTotalMailBlocks($numTotalMailBlocks)
    {
        $this->numTotalMailBlocks = $numTotalMailBlocks;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumSent()
    {
        return $this->numSent;
    }

    /**
     * @param int $numSent
     *
     * @return CampaignSummary
     */
    public function setNumSent($numSent)
    {
        $this->numSent = $numSent;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextSent()
    {
        return $this->numTextSent;
    }

    /**
     * @param int $numTextSent
     *
     * @return CampaignSummary
     */
    public function setNumTextSent($numTextSent)
    {
        $this->numTextSent = $numTextSent;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalSent()
    {
        return $this->numTotalSent;
    }

    /**
     * @param int $numTotalSent
     *
     * @return CampaignSummary
     */
    public function setNumTotalSent($numTotalSent)
    {
        $this->numTotalSent = $numTotalSent;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumRecipientsClicked()
    {
        return $this->numRecipientsClicked;
    }

    /**
     * @param int $numRecipientsClicked
     *
     * @return CampaignSummary
     */
    public function setNumRecipientsClicked($numRecipientsClicked)
    {
        $this->numRecipientsClicked = $numRecipientsClicked;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumDelivered()
    {
        return $this->numDelivered;
    }

    /**
     * @param int $numDelivered
     *
     * @return CampaignSummary
     */
    public function setNumDelivered($numDelivered)
    {
        $this->numDelivered = $numDelivered;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTextDelivered()
    {
        return $this->numTextDelivered;
    }

    /**
     * @param int $numTextDelivered
     *
     * @return CampaignSummary
     */
    public function setNumTextDelivered($numTextDelivered)
    {
        $this->numTextDelivered = $numTextDelivered;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumTotalDelivered()
    {
        return $this->numTotalDelivered;
    }

    /**
     * @param int $numTotalDelivered
     *
     * @return CampaignSummary
     */
    public function setNumTotalDelivered($numTotalDelivered)
    {
        $this->numTotalDelivered = $numTotalDelivered;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageDelivered()
    {
        return $this->percentageDelivered;
    }

    /**
     * @param float $percentageDelivered
     *
     * @return CampaignSummary
     */
    public function setPercentageDelivered($percentageDelivered)
    {
        $this->percentageDelivered = $percentageDelivered;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageUniqueOpens()
    {
        return $this->percentageUniqueOpens;
    }

    /**
     * @param float $percentageUniqueOpens
     *
     * @return CampaignSummary
     */
    public function setPercentageUniqueOpens($percentageUniqueOpens)
    {
        $this->percentageUniqueOpens = $percentageUniqueOpens;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageOpens()
    {
        return $this->percentageOpens;
    }

    /**
     * @param float $percentageOpens
     *
     * @return CampaignSummary
     */
    public function setPercentageOpens($percentageOpens)
    {
        $this->percentageOpens = $percentageOpens;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageUnsubscribes()
    {
        return $this->percentageUnsubscribes;
    }

    /**
     * @param float $percentageUnsubscribes
     *
     * @return CampaignSummary
     */
    public function setPercentageUnsubscribes($percentageUnsubscribes)
    {
        $this->percentageUnsubscribes = $percentageUnsubscribes;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageReplies()
    {
        return $this->percentageReplies;
    }

    /**
     * @param float $percentageReplies
     *
     * @return CampaignSummary
     */
    public function setPercentageReplies($percentageReplies)
    {
        $this->percentageReplies = $percentageReplies;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageHardBounces()
    {
        return $this->percentageHardBounces;
    }

    /**
     * @param float $percentageHardBounces
     *
     * @return CampaignSummary
     */
    public function setPercentageHardBounces($percentageHardBounces)
    {
        $this->percentageHardBounces = $percentageHardBounces;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageSoftBounces()
    {
        return $this->percentageSoftBounces;
    }

    /**
     * @param float $percentageSoftBounces
     *
     * @return CampaignSummary
     */
    public function setPercentageSoftBounces($percentageSoftBounces)
    {
        $this->percentageSoftBounces = $percentageSoftBounces;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageUsersClicked()
    {
        return $this->percentageUsersClicked;
    }

    /**
     * @param float $percentageUsersClicked
     *
     * @return CampaignSummary
     */
    public function setPercentageUsersClicked($percentageUsersClicked)
    {
        $this->percentageUsersClicked = $percentageUsersClicked;

        return $this;
    }

    /**
     * @return float
     */
    public function getPercentageClicksToOpens()
    {
        return $this->percentageClicksToOpens;
    }

    /**
     * @param float $percentageClicksToOpens
     *
     * @return CampaignSummary
     */
    public function setPercentageClicksToOpens($percentageClicksToOpens)
    {
        $this->percentageClicksToOpens = $percentageClicksToOpens;

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
     * @return CampaignSummary
     */
    public function setCreatedAt(?\DateTime $createdAt = null)
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
     * @return CampaignSummary
     */
    public function setUpdatedAt(?\DateTime $updatedAt = null)
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
     * @return CampaignSummary
     */
    public function setOwner(?Organization $owner = null)
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
     * @return CampaignSummary
     */
    public function setCampaign(?Campaign $campaign = null)
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
