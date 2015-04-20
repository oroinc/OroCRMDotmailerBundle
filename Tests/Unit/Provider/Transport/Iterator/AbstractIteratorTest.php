<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractIterator;

class AbstractIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractIterator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $iterator;

    protected function setUp()
    {
        $this->iterator = $this->getMockBuilder(
            'OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractIterator'
        )
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();
    }

    public function testIteratorIterateAllItems()
    {
        $expectedItems = [
            ['first expected item'],
            ['second expected item'],
            ['third expected item'],
        ];

        $this->iterator
            ->expects($this->at(0))
            ->method('getItems')
            ->with($this->iterator->getBatchSize(), 0)
            ->will($this->returnValue($expectedItems));

        $actualItems = [];
        foreach ($this->iterator as $item) {
            $actualItems[] = $item;
        }
        $this->assertEquals($expectedItems, $actualItems);
    }

    public function testIteratorIterateAllItemsInTwoMoreBatches()
    {
        $expectedItems = [
            ['first expected item'],
            ['second expected item'],
            ['third expected item'],
        ];

        $this->iterator->setBatchSize(2);

        $this->iterator
            ->expects($this->at(0))
            ->method('getItems')
            ->with($this->iterator->getBatchSize(), 0)
            ->will($this->returnValue([$expectedItems[0], $expectedItems[1]]));
        $this->iterator
            ->expects($this->at(1))
            ->method('getItems')
            ->with($this->iterator->getBatchSize(), 2)
            ->will($this->returnValue([$expectedItems[2]]));

        $actualItems = [];
        foreach ($this->iterator as $item) {
            $actualItems[] = $item;
        }
        $this->assertEquals($expectedItems, $actualItems);
    }
}
