<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppression;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedFromAccountContactsIterator;

class UnsubscribedFromAccountContactsIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $expectedDate = new \DateTime();
        $iterator = new UnsubscribedFromAccountContactsIterator($resource, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiContactSuppressionList();
        $expectedContactSuppression = new ApiContactSuppression();
        $expectedContactSuppression['suppressedcontact'] = ['id' => 2];
        $items[] = $expectedContactSuppression;
        $resource->expects($this->exactly(2))
            ->method('GetContactsUnsubscribedSinceDate')
            ->with($expectedDate)
            ->will($this->returnValueMap(
                [
                    [$expectedDate, 1, 0, $items],
                    [$expectedDate, 1, 1, new ApiContactSuppressionList()],
                ]
            ));
        foreach ($iterator as $item) {
            $expectedCampaignArray = $expectedContactSuppression->toArray();
            $this->assertSame($expectedCampaignArray, $item);
        }
    }
}
