<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator\Stub\StubAbstractIterator;

class AbstractIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StubAbstractIterator
     */
    protected $iterator;

    protected function setUp()
    {
        $this->iterator = new StubAbstractIterator();
    }

    /**
     * @dataProvider iteratorDataProvider
     * @param int $batchSize
     * @param array $items
     * @param array $expectedItems
     * @param int $expectedLoadCount
     */
    public function testIteratorWorks($batchSize, array $items, array $expectedItems, $expectedLoadCount)
    {
        $this->iterator->setBatchSize($batchSize);
        $this->iterator->initStub($items);

        $actualItems = [];
        foreach ($this->iterator as $index => $item) {
            $actualItems[] = [$index => $item];
        }
        $this->assertEquals($expectedItems, $actualItems);
        $this->assertEquals($expectedLoadCount, $this->iterator->getLoadCount());
    }

    public function iteratorDataProvider()
    {
        $items = [
            0 => ['first expected item'],
            1 => ['second expected item'],
            2 => ['third expected item'],
            3 => ['fourth expected item'],
        ];

        return [
            '1 batch' => [
                'batchSize' => 1000,
                'items' => [
                    $items[0],
                    $items[1],
                    $items[2],
                ],
                'expectedItems' => [
                    [0 => $items[0]],
                    [1 => $items[1]],
                    [2 => $items[2]],
                ],
                'expectedLoadCount' => 1,
            ],
            '2 batches' => [
                'batchSize' => 2,
                'items' => [
                    $items[0],
                    $items[1],
                    $items[2],
                ],
                'expectedItems' => [
                    [0 => $items[0]],
                    [1 => $items[1]],
                    [2 => $items[2]],
                ],
                'expectedLoadCount' => 2,
            ],
            '3 batches, last batch is empty' => [
                'batchSize' => 2,
                'items' => [
                    $items[0],
                    $items[1],
                    $items[2],
                    $items[3],
                ],
                'expectedItems' => [
                    [0 => $items[0]],
                    [1 => $items[1]],
                    [2 => $items[2]],
                    [3 => $items[3]],
                ],
                'expectedLoadCount' => 3,
            ],
        ];
    }
}
