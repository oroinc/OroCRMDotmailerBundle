<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppression;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactsIterator;

class UnsubscribedContactsIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $expectedAddressBookOriginId = 42;
        $expectedDate = new \DateTime();
        $iterator = new UnsubscribedContactsIterator($resource, $expectedAddressBookOriginId, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiContactSuppressionList();
        $expectedContactSuppression = new ApiContactSuppression();
        $expectedContactSuppression['suppressedcontact'] = ['id' => 2];
        $items[] = $expectedContactSuppression;
        $resource->expects($this->exactly(2))
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with($expectedAddressBookOriginId, $expectedDate)
            ->will($this->returnValueMap(
                [
                    [$expectedAddressBookOriginId, $expectedDate, 1, 0, $items],
                    [$expectedAddressBookOriginId, $expectedDate, 1, 1, new ApiContactSuppressionList()],
                ]
            ));
        foreach ($iterator as $item) {
            $expectedCampaignArray = $expectedContactSuppression->toArray();
            $expectedCampaignArray[UnsubscribedContactsIterator::ADDRESS_BOOK_KEY] = $expectedAddressBookOriginId;
            $this->assertSame($expectedCampaignArray, $item);
        }
    }
}
