<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator\Stub;

trait StubIteratorTrait
{
    protected array $stubItems;
    protected int $loadCount;

    public function initStub(array $stubItems)
    {
        $this->stubItems = $stubItems;
        $this->loadCount = 0;
    }

    public function getLoadCount(): int
    {
        return $this->loadCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        ++$this->loadCount;

        return \array_slice($this->stubItems, $skip, $take);
    }
}
