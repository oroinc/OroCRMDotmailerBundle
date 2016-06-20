<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity(repositoryClass="OroCRM\Bundle\DotmailerBundle\Entity\Repository\ActivityRepository")
 * @ORM\Table(
 *      name="orocrm_dm_activity",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_activity_unq", columns={"campaign_id", "contact_id", "channel_id"})
 *     },
 *     indexes={
 *          @ORM\Index(name="orocrm_dm_activity_email_idx", columns={"email"}),
 *          @ORM\Index(name="orocrm_dm_activity_dt_sent_idx", columns={"date_sent"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-user",
 *          "category"="account_management"
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
class Activity implements ChannelAwareInterface
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
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;

    /**
     * @var int
     *
     * @ORM\Column(name="num_opens", type="integer", nullable=true)
     */
    protected $numOpens;

    /**
     * @var int
     *
     * @ORM\Column(name="num_page_views", type="integer", nullable=true)
     */
    protected $numPageViews;

    /**
     * @var int
     *
     * @ORM\Column(name="num_clicks", type="integer", nullable=true)
     */
    protected $numClicks;

    /**
     * @var int
     *
     * @ORM\Column(name="num_forwards", type="integer", nullable=true)
     */
    protected $numForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_estimated_forwards", type="integer", nullable=true)
     */
    protected $numEstimatedForwards;

    /**
     * @var int
     *
     * @ORM\Column(name="num_replies", type="integer", nullable=true)
     */
    protected $numReplies;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    protected $dateSent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_first_opened", type="datetime", nullable=true)
     */
    protected $dateFirstOpened;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_last_opened", type="datetime", nullable=true)
     */
    protected $dateLastOpened;

    /**
     * @var string
     *
     * @ORM\Column(name="first_open_ip", type="string", length=255, nullable=true)
     */
    protected $firstOpenIp;

    /**
     * @var bool
     *
     * @ORM\Column(name="unsubscribed", type="boolean", nullable=true)
     */
    protected $unsubscribed;

    /**
     * @var bool
     *
     * @ORM\Column(name="soft_bounced", type="boolean", nullable=true)
     */
    protected $softBounced;

    /**
     * @var bool
     *
     * @ORM\Column(name="hard_bounced", type="boolean", nullable=true)
     */
    protected $hardBounced;

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
     * @var Campaign
     *
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\Campaign", inversedBy="activities")
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
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\DotmailerBundle\Entity\Contact", inversedBy="activities")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $contact;

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
     * @return Activity
     */
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
     * @param Contact $contact
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
     * @param \DateTime $dateSent
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
     * @param \DateTime $dateFirstOpened
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
     * @param \DateTime $dateLastOpened
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
     * @param \DateTime $createdAt
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
     * @param \DateTime $updatedAt
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
     * @param Organization $owner
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
     * @param Campaign $campaign
     *
     * @return Activity
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
