<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Store data field mapping in a database.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository")
 * @ORM\Table(
 *      name="orocrm_dm_df_mapping",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_data_field_unq", columns={"entity", "channel_id"})
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
 *          "group_name"="",
 *          "category"="marketing"
 *      }
 *  }
 * )
 */
class DataFieldMapping implements ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

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
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $channel;

    /**
     * @var string $entity
     *
     * @ORM\Column(name="entity", type="string", length=255)
     */
    protected $entity;

    /**
     * @var int
     *
     * @ORM\Column(name="sync_priority", type="integer", nullable=true)
     */
    protected $syncPriority;

    /**
     * @var Collection|DataFieldMappingConfig[]
     *
     * @ORM\OneToMany(targetEntity="DataFieldMappingConfig",
     *     mappedBy="mapping", cascade={"all"}, orphanRemoval=true
     * )
     */
    protected $configs;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->configs  = new ArrayCollection();
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
     * @return DataFieldMapping
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     * @return DataFieldMapping
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyncPriority()
    {
        return $this->syncPriority;
    }

    /**
     * @param int $syncPriority
     * @return DataFieldMapping
     */
    public function setSyncPriority($syncPriority)
    {
        $this->syncPriority = $syncPriority;

        return $this;
    }

    /**
     * @return Collection|DataFieldMappingConfig[]
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @param Collection|DataFieldMappingConfig[] $configs
     * @return DataFieldMapping
     */
    public function setConfigs($configs)
    {
        $this->configs = $configs;

        return $this;
    }

    /**
     * @param DataFieldMappingConfig $config
     *
     * @return DataFieldMapping
     */
    public function addConfig(DataFieldMappingConfig $config)
    {
        if (!$this->configs->contains($config)) {
            $config->setMapping($this);
            $this->configs->add($config);
        }

        return $this;
    }

    /**
     * @param DataFieldMappingConfig $config
     *
     * @return DataFieldMapping
     */
    public function removeConfig(DataFieldMappingConfig $config)
    {
        if ($this->configs->contains($config)) {
            $this->configs->removeElement($config);
        }

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
     * @return DataFieldMapping
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
