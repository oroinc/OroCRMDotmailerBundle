<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity(repositoryClass="OroCRM\Bundle\DotmailerBundle\Entity\Repository\CampaignSummaryRepository")
 * @ORM\Table(
 *     name="orocrm_dm_campaign_summary",
 *     indexes={
 *          @ORM\Index(name="orocrm_dm_camp_sum_dt_sent_idx", columns={"date_sent"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-user"
 *      },
 *      "ownership"={
 *          "owner_type"="ORGANIZATION",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="owner_id"
 *      },
 *      "security"={
 *          "type"="ACL",
 *          "group_name"="",
 *          "category"="marketing"
 *      }
 *  }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CampaignSummary implements ChannelAwareInterface
{
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
     * @var Campaign
     *
     * @ORM\OneToOne(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\Campaign", inversedBy="campaignSummary")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $campaign;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    protected $dateSent;

    /**
     * @var int
     *
     * @ORM\Column(name="num_unique_opens", type="integer", nullable=true)
     */
    protected $numUniqueOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_unique_text_opens", type="integer", nullable=true)
     */
    protected $numUniqueTextOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_unique_opens", type="integer", nullable=true)
     */
    protected $numTotalUniqueOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_opens", type="integer", nullable=true)
     */
    protected $numOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_opens", type="integer", nullable=true)
     */
    protected $numTextOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_opens", type="integer", nullable=true)
     */
    protected $numTotalOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_clicks", type="integer", nullable=true)
     */
    protected $numClicks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_clicks", type="integer", nullable=true)
     */
    protected $numTextClicks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_clicks", type="integer", nullable=true)
     */
    protected $numTotalClicks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_page_views", type="integer", nullable=true)
     */
    protected $numPageViews;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_page_views", type="integer", nullable=true)
     */
    protected $numTotalPageViews;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_page_views", type="integer", nullable=true)
     */
    protected $numTextPageViews;

    /**
     * @var int
     *
     * @ORM\Column(name="num_forwards", type="integer", nullable=true)
     */
    protected $numForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_forwards", type="integer", nullable=true)
     */
    protected $numTextForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_estimated_forwards", type="integer", nullable=true)
     */
    protected $numEstimatedForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_estimated_forwards", type="integer", nullable=true)
     */
    protected $numTextEstimatedForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_estimated_forwards", type="integer", nullable=true)
     */
    protected $numTotalEstimatedForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_replies", type="integer", nullable=true)
     */
    protected $numReplies;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_replies", type="integer", nullable=true)
     */
    protected $numTextReplies;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_replies", type="integer", nullable=true)
     */
    protected $numTotalReplies;

    /**
     * @var int
     *
     * @ORM\Column(name="num_hard_bounces", type="integer", nullable=true)
     */
    protected $numHardBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_hard_bounces", type="integer", nullable=true)
     */
    protected $numTextHardBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_hard_bounces", type="integer", nullable=true)
     */
    protected $numTotalHardBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_soft_bounces", type="integer", nullable=true)
     */
    protected $numSoftBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_soft_bounces", type="integer", nullable=true)
     */
    protected $numTextSoftBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_soft_bounces", type="integer", nullable=true)
     */
    protected $numTotalSoftBounces;

    /**
     * @var int
     *
     * @ORM\Column(name="num_unsubscribes", type="integer", nullable=true)
     */
    protected $numUnsubscribes;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_unsubscribes", type="integer", nullable=true)
     */
    protected $numTextUnsubscribes;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_unsubscribes", type="integer", nullable=true)
     */
    protected $numTotalUnsubscribes;

    /**
     * @var int
     *
     * @ORM\Column(name="num_isp_complaints", type="integer", nullable=true)
     */
    protected $numIspComplaints;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_isp_complaints", type="integer", nullable=true)
     */
    protected $numTextIspComplaints;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_isp_complaints", type="integer", nullable=true)
     */
    protected $numTotalIspComplaints;

    /**
     * @var int
     *
     * @ORM\Column(name="num_mail_blocks", type="integer", nullable=true)
     */
    protected $numMailBlocks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_mail_blocks", type="integer", nullable=true)
     */
    protected $numTextMailBlocks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_mail_blocks", type="integer", nullable=true)
     */
    protected $numTotalMailBlocks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_sent", type="integer", nullable=true)
     */
    protected $numSent;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_sent", type="integer", nullable=true)
     */
    protected $numTextSent;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_sent", type="integer", nullable=true)
     */
    protected $numTotalSent;

    /**
     * @var int
     *
     * @ORM\Column(name="num_recipients_clicked", type="integer", nullable=true)
     */
    protected $numRecipientsClicked;

    /**
     * @var int
     *
     * @ORM\Column(name="num_delivered", type="integer", nullable=true)
     */
    protected $numDelivered;

    /**
     * @var int
     *
     * @ORM\Column(name="num_text_delivered", type="integer", nullable=true)
     */
    protected $numTextDelivered;

    /**
     * @var int
     *
     * @ORM\Column(name="num_total_delivered", type="integer", nullable=true)
     */
    protected $numTotalDelivered;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_delivered", type="float", nullable=true)
     */
    protected $percentageDelivered;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_unique_opens", type="float", nullable=true)
     */
    protected $percentageUniqueOpens;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_opens", type="float", nullable=true)
     */
    protected $percentageOpens;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_unsubscribes", type="float", nullable=true)
     */
    protected $percentageUnsubscribes;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_replies", type="float", nullable=true)
     */
    protected $percentageReplies;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_hard_bounces", type="float", nullable=true)
     */
    protected $percentageHardBounces;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_soft_bounces", type="float", nullable=true)
     */
    protected $percentageSoftBounces;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_users_clicked", type="float", nullable=true)
     */
    protected $percentageUsersClicked;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage_clicks_to_opens", type="float", nullable=true)
     */
    protected $percentageClicksToOpens;

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
     * @return CampaignSummary
     */
    public function setChannel(Channel $channel = null)
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
     * @param \DateTime $createdAt
     *
     * @return CampaignSummary
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
     * @param \DateTime $updatedAt
     *
     * @return CampaignSummary
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
     * @param Organization $owner
     *
     * @return CampaignSummary
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
     * @param Campaign $campaign
     *
     * @return CampaignSummary
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

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
