<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Changed field log entity
 * @ORM\Table(
 *      name="orocrm_dm_change_field_log",
 * )
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
 * @ORM\Entity(repositoryClass="Oro\Bundle\DotmailerBundle\Entity\Repository\ChangedFieldLogRepository")
 */
class ChangedFieldLog
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
     * @var int
     *
     * @ORM\Column(name="channel_id", type="integer")
     */
    protected $channelId;

    /**
     * Entity, which was used in mapping configuration
     *
     * @var string
     *
     * @ORM\Column(name="parent_entity", type="string", length=255)
     */
    protected $parentEntity;

    /**
     * Relation path to the modified field
     *
     * @var string
     *
     * @ORM\Column(name="related_field_path", type="text")
     */
    protected $relatedFieldPath;

    /**
     * Id of related entity which was changed
     *
     * @var int
     *
     * @ORM\Column(name="related_id", type="integer", nullable=true)
     */
    protected $relatedId;

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
