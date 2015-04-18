<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

abstract class AbstractIterator implements \Iterator
{
    /**
     * @var int
     */
    protected $pageNumber = 0;

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
    protected $batchSize = 10000;

    /**
     * @var bool
     */
    protected $isValid = true;

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
        if (next($this->items) === false && !$this->tryToLoadItems()) {
            $this->isValid = false;
        }
    }

    /**
     * @return bool
     */
    protected function tryToLoadItems()
    {
        $this->items = $this->getItems($this->batchSize, $this->batchSize * $this->pageNumber);
        return count($this->items) > 0;
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
        return $this->isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->isValid = true;
        $this->items = [];
        $this->currentItemIndex = 0;
        $this->pageNumber = 0;

        $this->tryToLoadItems();
    }

    /**
     * @param int $select Count of requested records
     * @param int $skip Count of skipped records
     * @return array
     */
    abstract protected function getItems($select, $skip);
}
