<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

use OroCRM\Bundle\DotmailerBundle\Provider\CsvStringReader;

class ExportFaultsReportIterator implements \Iterator
{
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
    public function rewind()
    {
        $this->offset  = -1;
        $this->current = null;
        $this->reader  = null;
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->current = $this->getReader()->read();
        if ($this->valid()) {
            $this->offset += 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return !is_null($this->current);
    }

    /**
     * @return CsvStringReader
     */
    protected function getReader()
    {
        if (!$this->reader) {
            $csv = $this->resources->GetContactsImportReportFaults($this->importId);
            $this->reader = new CsvStringReader($csv, $this->options);
        }
        return $this->reader;
    }
}
