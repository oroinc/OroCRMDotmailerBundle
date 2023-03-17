<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator\Stub\StubAbstractIterator;

class AbstractIteratorTest extends \PHPUnit\Framework\TestCase
{
    protected StubAbstractIterator $iterator;

    protected function setUp(): void
    {
        $this->iterator = new StubAbstractIterator();
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWorks(int $batchSize, array $items, array $expectedItems, int $expectedLoadCount)
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

    public function iteratorDataProvider(): array
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
