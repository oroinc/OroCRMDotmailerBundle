<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ChangedFieldLogRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Changed field log entity
 */
#[ORM\Entity(repositoryClass: ChangedFieldLogRepository::class)]
#[ORM\Table(name: 'orocrm_dm_change_field_log')]
#[Config(defaultValues: ['activity' => ['immutable' => true], 'attachment' => ['immutable' => true]])]
class ChangedFieldLog
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'channel_id', type: Types::INTEGER)]
    protected ?int $channelId = null;

    /**
     * Entity, which was used in mapping configuration
     */
    #[ORM\Column(name: 'parent_entity', type: Types::STRING, length: 255)]
    protected ?string $parentEntity = null;

    /**
     * Relation path to the modified field
     */
    #[ORM\Column(name: 'related_field_path', type: Types::TEXT)]
    protected ?string $relatedFieldPath = null;

    /**
     * Id of related entity which was changed
     */
    #[ORM\Column(name: 'related_id', type: Types::INTEGER, nullable: true)]
    protected ?int $relatedId = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     * @return ChangedFieldLog
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }

    /**
     * @param string $parentEntity
     * @return ChangedFieldLog
     */
    public function setParentEntity($parentEntity)
    {
        $this->parentEntity = $parentEntity;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelatedId()
    {
        return $this->relatedId;
    }

    /**
     * @param int $relatedId
     * @return ChangedFieldLog
     */
    public function setRelatedId($relatedId)
    {
        $this->relatedId = $relatedId;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedFieldPath()
    {
        return $this->relatedFieldPath;
    }

    /**
     * @param string $relatedFieldPath
     * @return ChangedFieldLog
     */
    public function setRelatedFieldPath($relatedFieldPath)
    {
        $this->relatedFieldPath = $relatedFieldPath;

        return $this;
    }
}
