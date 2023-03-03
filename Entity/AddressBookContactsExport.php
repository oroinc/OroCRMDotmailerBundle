<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * AddressBookContactsExport ORM entity.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository")
 * @ORM\Table(
 *      name="orocrm_dm_ab_cnt_export",
 *     indexes={
 *          @ORM\Index(name="orocrm_dm_ab_cnt_exp_fault_idx", columns={"faults_processed"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config()
 *
 * @method AbstractEnumValue getStatus()
 * @method AddressBookContactsExport setStatus(AbstractEnumValue $enumValue)
 */
class AddressBookContactsExport implements ChannelAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    const STATUS_NOT_FINISHED = 'NotFinished';
    const STATUS_FINISH = 'Finished';
    const STATUS_REJECTED_BY_WATCHDOG = 'RejectedByWatchdog';
    const STATUS_INVALID_FILE_FORMAT = 'InvalidFileFormat';
    const STATUS_UNKNOWN = 'Unknown';
    const STATUS_FAILED = 'Failed';
    const STATUS_EXCEEDS_ALLOWED_CONTACT_LIMIT = 'ExceedsAllowedContactLimit';
    const STATUS_NOT_AVAILABLE_IN_THIS_VERSION = 'NotAvailableInThisVersion';

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
     * @ORM\Column(name="import_id", type="string", length=100, unique=true, nullable=false)
     */
    protected $importId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var AddressBook
     *
     * @ORM\ManyToOne(targetEntity="AddressBook", inversedBy="addressBookContactsExports")
     * @ORM\JoinColumn(name="address_book_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $addressBook;

    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $channel;

    /**
     * @var bool
     *
     * @ORM\Column(name="faults_processed", type="boolean")
     */
    protected $faultsProcessed = false;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", name="sync_attempts", nullable=true, options={"unsigned"=true})
     */
    protected $syncAttempts;

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
     * @param \DateTime $createdAt
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
     * @param AddressBook $addressBook
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
     * @param \DateTime $updatedAt
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
     * @param Channel $channel
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
