<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\Guid;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Provider\CsvStringReader;

/**
 * Iterates over contacts report faults
 */
class ExportFaultsReportIterator implements \Iterator
{
    const ADDRESS_BOOK_ID = 'address_book_id';
    const IMPORT_ID = 'import_id';

    /**
     * @var IResources
     */
    protected $resources;

    /**
     * @var int
     */
    protected $addressBookId;

    /**
     * @var string
     */
    protected $importId;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var CsvStringReader
     */
    protected $reader;

    /**
     * @var mixed
     */
    protected $current = null;

    /**
     * @var mixed
     */
    protected $offset = -1;

    /**
     * @param IResources $resources
     * @param int        $addressBookId
     * @param string     $importId
     * @param array      $options
     */
    public function __construct(IResources $resources, $addressBookId, $importId, $options = [])
    {
        $this->resources = $resources;
        $this->addressBookId = $addressBookId;
        $this->importId = $importId;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->offset  = -1;
        $this->current = null;
        $this->reader  = null;
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        $item = $this->current;

        $item[self::ADDRESS_BOOK_ID] = $this->addressBookId;
        $item[self::IMPORT_ID] = $this->importId;

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->current = $this->getReader()->read();
        if ($this->valid()) {
            $this->offset += 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return !is_null($this->current);
    }

    /**
     * @return CsvStringReader
     */
    protected function getReader()
    {
        if (!$this->reader) {
            $csv = $this->resources->GetContactsImportReportFaults(new Guid($this->importId));
            $this->reader = new CsvStringReader($csv, $this->options);
        }
        return $this->reader;
    }
}
