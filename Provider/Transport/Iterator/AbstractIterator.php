<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

abstract class AbstractIterator implements \Iterator
{
    const DEFAULT_BATCH_SIZE = 1000;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $currentItemIndex = 0;

    /**
     * @var int
     */
    protected $batchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * @var bool
     */
    protected $isValid = true;

    /**
     * @var bool
     */
    protected $lastPage = false;

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (next($this->items) !== false || $this->tryToLoadItems($this->currentItemIndex + 1)) {
            $this->currentItemIndex++;
        }
    }

    /**
     * @param int $skip
     *
     * @return bool
     */
    protected function tryToLoadItems($skip = 0)
    {
        /** Requests count optimization */
        if ($this->lastPage) {
            return false;
        }

        $this->items = $this->getItems($this->batchSize, $skip);
        reset($this->items);

        if (count($this->items) == 0) {
            return false;
        }

        if (count($this->items) < $this->batchSize) {
            $this->lastPage = true;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->currentItemIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $isValid = $this->isValid && current($this->items) !== false;
        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->lastPage = false;
        $this->items = [];
        $this->currentItemIndex = 0;

        $this->isValid = $this->tryToLoadItems();
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    abstract protected function getItems($take, $skip);

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     *
     * @return AbstractIterator
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;

        return $this;
    }
}
