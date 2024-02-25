<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_DataField;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Dotmailer DataField entity.
 *
 * @method AbstractEnumValue getType()
 * @method DataField setType(AbstractEnumValue $enumValue)
 * @method AbstractEnumValue getVisibility()
 * @method DataField setVisibility(AbstractEnumValue $enumValue)
 * @mixin OroDotmailerBundle_Entity_DataField
 */
#[ORM\Entity(repositoryClass: DataFieldRepository::class)]
#[ORM\Table(name: 'orocrm_dm_data_field')]
#[ORM\UniqueConstraint(name: 'orocrm_dm_data_field_unq', columns: ['name', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'icon-group'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class DataField implements ChannelAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    /** constant for enum dm_df_visibility */
    const VISIBILITY_PRIVATE                    = 'Private';
    const VISIBILITY_PUBLIC                     = 'Public';

    /** constant for enum dm_df_field_type */
    const FIELD_TYPE_STRING                     = 'String';
    const FIELD_TYPE_NUMERIC                    = 'Numeric';
    const FIELD_TYPE_DATE                       = 'Date';
    const FIELD_TYPE_BOOLEAN                    = 'Boolean';

    //default values for Boolean field type
    const DEFAULT_BOOLEAN_YES = 'Yes';
    const DEFAULT_BOOLEAN_NO = 'No';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => false]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'default_value', type: Types::STRING, length: 255, nullable: true)]
    protected $defaultValue;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    /**
     * Flag used for force field remove when fields import from DM is running
     *
     * @var bool
     */
    protected $forceRemove;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Organization $owner = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $createdAt = null;

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
     * @return DataField
     */
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
     * @return DataField
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
     * @return DataField
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     * @return DataField
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return DataField
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isForceRemove()
    {
        return $this->forceRemove;
    }

    /**
     * @param boolean $forceRemove
     * @return DataField
     */
    public function setForceRemove($forceRemove)
    {
        $this->forceRemove = $forceRemove;

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
     * @return DataField
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }
}
