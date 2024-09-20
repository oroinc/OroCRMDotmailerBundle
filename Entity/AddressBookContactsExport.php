<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDotmailerBundle_Entity_AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * AddressBookContactsExport ORM entity.
 *
 *
 * @method EnumOptionInterface getStatus()
 * @method AddressBookContactsExport setStatus(EnumOptionInterface $enumOption)
 * @mixin OroDotmailerBundle_Entity_AddressBookContactsExport
 */
#[ORM\Entity(repositoryClass: AddressBookContactsExportRepository::class)]
#[ORM\Table(name: 'orocrm_dm_ab_cnt_export')]
#[ORM\Index(columns: ['faults_processed'], name: 'orocrm_dm_ab_cnt_exp_fault_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config]
class AddressBookContactsExport implements ChannelAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const STATUS_ENUM_CODE = 'dm_import_status';
    public const STATUS_NOT_FINISHED = 'NotFinished';
    public const STATUS_FINISH = 'Finished';
    public const STATUS_REJECTED_BY_WATCHDOG = 'RejectedByWatchdog';
    public const STATUS_INVALID_FILE_FORMAT = 'InvalidFileFormat';
    public const STATUS_UNKNOWN = 'Unknown';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_EXCEEDS_ALLOWED_CONTACT_LIMIT = 'ExceedsAllowedContactLimit';
    public const STATUS_NOT_AVAILABLE_IN_THIS_VERSION = 'NotAvailableInThisVersion';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'import_id', type: Types::STRING, length: 100, unique: true, nullable: false)]
    protected ?string $importId = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: AddressBook::class, inversedBy: 'addressBookContactsExports')]
    #[ORM\JoinColumn(name: 'address_book_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?AddressBook $addressBook = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'faults_processed', type: Types::BOOLEAN)]
    protected ?bool $faultsProcessed = false;

    #[ORM\Column(name: 'sync_attempts', type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    protected ?int $syncAttempts = null;

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
    public function getImportId()
    {
        return $this->importId;
    }

    /**
     * @param string $importId
     *
     * @return AddressBookContactsExport
     */
    public function setImportId($importId)
    {
        $this->importId = $importId;

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
     * @return AddressBookContactsExport
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return AddressBook
     */
    public function getAddressBook()
    {
        return $this->addressBook;
    }

    /**
     * @param AddressBook|null $addressBook
     *
     * @return AddressBookContactsExport
     */
    public function setAddressBook(AddressBook $addressBook = null)
    {
        $this->addressBook = $addressBook;

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
     * @return AddressBookContactsExport
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel|null $channel
     *
     * @return AddressBookContactsExport
     */
    public function setChannel(Channel $channel = null)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFaultsProcessed()
    {
        return $this->faultsProcessed;
    }

    /**
     * @param bool $faultsProcessed
     *
     * @return AddressBookContactsExport
     */
    public function setFaultsProcessed($faultsProcessed)
    {
        $this->faultsProcessed = $faultsProcessed;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyncAttempts()
    {
        return $this->syncAttempts;
    }

    /**
     * @param int $syncAttempts
     * @return AddressBookContactsExport
     */
    public function setSyncAttempts(int $syncAttempts)
    {
        $this->syncAttempts = $syncAttempts;

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
