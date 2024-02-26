<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Data field mapping config entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_dm_df_mapping_config')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_df_mapping_config_unq', columns: ['datafield_id', 'mapping_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['activity' => ['immutable' => true], 'attachment' => ['immutable' => true]])]
class DataFieldMappingConfig
{
    use DatesAwareTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'entity_field', type: Types::TEXT)]
    protected ?string $entityFields = null;

    #[ORM\ManyToOne(targetEntity: DataField::class)]
    #[ORM\JoinColumn(name: 'datafield_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?DataField $dataField = null;

    /**
     * Controls whether we need to sync field values from Dotmailer into Oro
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_two_way_sync', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $isTwoWaySync = null;

    #[ORM\ManyToOne(targetEntity: DataFieldMapping::class, inversedBy: 'configs')]
    #[ORM\JoinColumn(name: 'mapping_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?DataFieldMapping $mapping = null;

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
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
