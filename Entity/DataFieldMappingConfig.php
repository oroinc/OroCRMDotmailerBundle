<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Data field mapping config entity
 * @ORM\Table(
 *      name="orocrm_dm_df_mapping_config",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orocrm_dm_df_mapping_config_unq", columns={"datafield_id", "mapping_id"})
 *     }
  * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class DataFieldMappingConfig
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_field", type="text")
     */
    protected $entityFields;

    /**
     * @var DataField
     *
     * @ORM\ManyToOne(targetEntity="DataField")
     * @ORM\JoinColumn(name="datafield_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $dataField;

    /**
     * Controls whether we need to sync field values from Dotmailer into Oro
     *
     * @var bool
     *
     * @ORM\Column(name="is_two_way_sync", type="boolean", nullable=true)
     */
    protected $isTwoWaySync;

    /**
     * @var DataFieldMapping
     *
     * @ORM\ManyToOne(targetEntity="DataFieldMapping", inversedBy="configs")
     * @ORM\JoinColumn(name="mapping_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $mapping;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntityFields()
    {
        return $this->entityFields;
    }

    /**
     * @param string $entityFields
     * @return DataFieldMappingConfig
     */
    public function setEntityFields($entityFields)
    {
        $this->entityFields = $entityFields;

        return $this;
    }

    /**
     * @return DataField
     */
    public function getDataField()
    {
        return $this->dataField;
    }

    /**
     * @param DataField $dataField
     * @return DataFieldMappingConfig
     */
    public function setDataField($dataField)
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isIsTwoWaySync()
    {
        return $this->isTwoWaySync;
    }

    /**
     * @param boolean $isTwoWaySync
     * @return DataFieldMappingConfig
     */
    public function setIsTwoWaySync($isTwoWaySync)
    {
        $this->isTwoWaySync = $isTwoWaySync;

        return $this;
    }

    /**
     * @return DataFieldMapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param DataFieldMapping $mapping
     * @return DataFieldMappingConfig
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;

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
