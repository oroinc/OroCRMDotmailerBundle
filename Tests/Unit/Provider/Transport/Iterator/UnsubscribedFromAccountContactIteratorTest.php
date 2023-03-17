<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppression;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedFromAccountContactIterator;

class UnsubscribedFromAccountContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $resource = $this->createMock(IResources::class);
        $expectedDate = new \DateTime();
        $iterator = new UnsubscribedFromAccountContactIterator($resource, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiContactSuppressionList();
        $expectedContactSuppression = new ApiContactSuppression();
        $expectedContactSuppression['suppressedcontact'] = ['id' => 2];
        $items[] = $expectedContactSuppression;
        $resource->expects($this->exactly(2))
            ->method('GetContactsSuppressedSinceDate')
            ->with($expectedDate->format(\DateTime::ISO8601))
            ->willReturnMap([
                [$expectedDate->format(\DateTime::ISO8601), 1, 0, $items],
                [$expectedDate->format(\DateTime::ISO8601), 1, 1, new ApiContactSuppressionList()],
            ]);
        foreach ($iterator as $item) {
            $expectedCampaignArray = $expectedContactSuppression->toArray();
            $this->assertSame($expectedCampaignArray, $item);
        }
    }
}
