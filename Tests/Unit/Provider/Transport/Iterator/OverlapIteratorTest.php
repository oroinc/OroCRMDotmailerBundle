<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator\Stub\StubOverlapIterator;

class OverlapIteratorTest extends \PHPUnit\Framework\TestCase
{
    private StubOverlapIterator $iterator;

    protected function setUp(): void
    {
        $this->iterator = new StubOverlapIterator();
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWorks(
        int $batchSize,
        int $overlap,
        array $items,
        array $expectedItems,
        int $expectedLoadCount
    ) {
        $this->iterator->setBatchSize($batchSize);
        $this->iterator->setOverlapSize($overlap);
        $this->iterator->initStub($items);

        $actualItems = [];
        foreach ($this->iterator as $index => $item) {
            $actualItems[] = [$index => $item];
        }
        $this->assertEquals($expectedItems, $actualItems);
        $this->assertEquals($expectedLoadCount, $this->iterator->getLoadCount());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function iteratorDataProvider(): array
    {
        $items = [
            0 => ['first expected item'],
            1 => ['second expected item'],
            2 => ['third expected item'],
            3 => ['fourth expected item'],
            4 => ['fifth expected item'],
            5 => ['sixth expected item'],
            6 => ['seventh expected item'],
            7 => ['eights expected item'],
            8 => ['nines expected item'],
            9 => ['tens expected item'],
            10 => ['eleventh expected item'],
        ];

        return [
            'without overlap, 1 batch' => [
                'batchSize' => 1000,
                'overlap' => 0,
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
            'without overlap, 2 batches' => [
                'batchSize' => 2,
                'overlap' => 0,
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
            'with overlap, 3 batches' => [
                'batchSize' => 5,
                'overlap' => 2,
                'items' => [
                    $items[0],
                    $items[1],
                    $items[2],
                    $items[3],
                    $items[4],
                    $items[5],
                    $items[6],
                    $items[7],
                    $items[8],
                    $items[9],
                ],
                'expectedItems' => [
                    [0 => $items[0]],
                    [1 => $items[1]],
                    [2 => $items[2]],
                    [3 => $items[3]],
                    [4 => $items[4]],
                    [3 => $items[3]],
                    [4 => $items[4]],
                    [5 => $items[5]],
                    [6 => $items[6]],
                    [7 => $items[7]],
                    [6 => $items[6]],
                    [7 => $items[7]],
                    [8 => $items[8]],
                    [9 => $items[9]],
                ],
                'expectedLoadCount' => 3,
            ],
            'with overlap, 4 batches, last batch only for overlap' => [
                'batchSize' => 5,
                'overlap' => 2,
                'items' => [
                    $items[0],
                    $items[1],
                    $items[2],
                    $items[3],
                    $items[4],
                    $items[5],
                    $items[6],
                    $items[7],
                    $items[8],
                    $items[9],
                    $items[10],
                ],
                'expectedItems' => [
                    [0 => $items[0]],
                    [1 => $items[1]],
                    [2 => $items[2]],
                    [3 => $items[3]],
                    [4 => $items[4]],
                    [3 => $items[3]],
                    [4 => $items[4]],
                    [5 => $items[5]],
                    [6 => $items[6]],
                    [7 => $items[7]],
                    [6 => $items[6]],
                    [7 => $items[7]],
                    [8 => $items[8]],
                    [9 => $items[9]],
                    [10 => $items[10]],
                    [9 => $items[9]],
                    [10 => $items[10]],
                ],
                'expectedLoadCount' => 4,
            ],
        ];
    }
}
