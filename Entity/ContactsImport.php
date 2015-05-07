<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="orocrm_dm_ab_cnt_import",
 * )
 */
class ContactsImport
{
    const STATUS_NOT_FINISHED = 'NotFinished';
    const STATUS_FINISH = 'Finished';
    const STATUS_REJECTED_BY_WATCHDOG = 'RejectedByWatchdog';
    const STATUS_INVALID_FILE_FORMAT = 'InvalidFileFormat';
    const STATUS_UNKNOWN = 'Unknown';
    const STATUS_FAILED = 'Failed';
    const STATUS_EXCEEDS_ALLOWED_CONTACT_LIMIT = 'ExceedsAllowedContactLimit';
    const STATUS_NOT_AVAILABLE_IN__THIS_VERSION = 'NotAvailableInThisVersion';

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
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    protected $status;

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
     * @ORM\ManyToOne(targetEntity="AddressBook")
     * @ORM\JoinColumn(name="address_book_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $addressBook;

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
     * @return ContactsImport
     */
    public function setImportId($importId)
    {
        $this->importId = $importId;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return ContactsImport
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * @return ContactsImport
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
     * @return ContactsImport
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
     * @return ContactsImport
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

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
