<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppression;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactIterator;

class UnsubscribedContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $resource = $this->createMock(IResources::class);
        $expectedAddressBookOriginId = 42;
        $expectedDate = new \DateTime();
        $iterator = new UnsubscribedContactIterator($resource, $expectedAddressBookOriginId, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiContactSuppressionList();
        $expectedContactSuppression = new ApiContactSuppression();
        $expectedContactSuppression['suppressedcontact'] = ['id' => 2];
        $items[] = $expectedContactSuppression;
        $resource->expects($this->exactly(2))
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with($expectedAddressBookOriginId, $expectedDate->format(\DateTime::ISO8601))
            ->willReturnMap([
                [
                    $expectedAddressBookOriginId,
                    $expectedDate->format(\DateTime::ISO8601),
                    1,
                    0,
                    $items
                ],
                [
                    $expectedAddressBookOriginId,
                    $expectedDate->format(\DateTime::ISO8601),
                    1,
                    1,
                    new ApiContactSuppressionList()
                ],
            ]);
        foreach ($iterator as $item) {
            $expectedCampaignArray = $expectedContactSuppression->toArray();
            $expectedCampaignArray[UnsubscribedContactIterator::ADDRESS_BOOK_KEY] = $expectedAddressBookOriginId;
            $this->assertSame($expectedCampaignArray, $item);
        }
    }

    public function testIteratorOverlap()
    {
        $resource = $this->createMock(IResources::class);
        $expectedAddressBookOriginId = 42;
        $expectedDate = new \DateTime();
        $iterator = new UnsubscribedContactIterator($resource, $expectedAddressBookOriginId, $expectedDate);
        $iterator->setBatchSize(200);

        $expectedItems = [];
        $firstBatch = new ApiContactSuppressionList();
        for ($itemNumber = 0; $itemNumber < 200; $itemNumber++) {
            $expectedContactSuppression = new ApiContactSuppression();
            $expectedContactSuppression['suppressedcontact'] = ['id' => $itemNumber];
            $firstBatch[] = $expectedContactSuppression;
            $expectedItems[] = $itemNumber;
        }

        $secondBatch = new ApiContactSuppressionList();
        for ($itemNumber = 200; $itemNumber < 400; $itemNumber++) {
            $expectedContactSuppression = new ApiContactSuppression();
            $expectedContactSuppression['suppressedcontact'] = ['id' => $itemNumber];
            $secondBatch[] = $expectedContactSuppression;
            $expectedItems[] = $itemNumber;
        }

        $resource->expects($this->exactly(3))
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->withConsecutive(
                [$expectedAddressBookOriginId, $expectedDate->format(\DateTime::ISO8601), 200, 0],
                [$expectedAddressBookOriginId, $expectedDate->format(\DateTime::ISO8601), 200, 100],
                [$expectedAddressBookOriginId, $expectedDate->format(\DateTime::ISO8601), 200, 200]
            )
            ->willReturnOnConsecutiveCalls($firstBatch, $secondBatch, new ApiContactSuppressionList());
        foreach ($iterator as $item) {
            $this->assertArrayHasKey('suppressedcontact', $item);
            $this->assertEquals(current($expectedItems), $item['suppressedcontact']['id']);
            next($expectedItems);
        }
    }
}
