<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator\Stub;

trait StubIteratorTrait
{
    /**
     * @var array
     */
    protected $stubItems;

    /**
     * @var int
     */
    protected $loadCount;

    /**
     * @param array $stubItems
     */
    public function initStub(array $stubItems)
    {
        $this->stubItems = $stubItems;
        $this->loadCount = 0;
    }

    /**
     * @return int
     */
    public function getLoadCount()
    {
        return $this->loadCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $this->loadCount += 1;
        return array_slice($this->stubItems, $skip, $take);
    }
}
