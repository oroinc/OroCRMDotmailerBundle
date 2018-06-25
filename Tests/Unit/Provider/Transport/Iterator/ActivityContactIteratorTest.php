<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiCampaignContactSummary;
use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;

class ActivityContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIteratorInitTrue()
    {
        $resource = $this->createMock('DotMailer\Api\Resources\IResources');
        $expectedCampaignOriginId = 15662;
        $expectedDate = new \DateTime();
        $iterator = new ActivityContactIterator($resource, $expectedCampaignOriginId, true, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactSummaryList();
        $expectedActivity = new ApiCampaignContactSummary();
        $expectedActivity->contactId = 2;
        $items[] = $expectedActivity;
        $resource->expects($this->any())
            ->method('GetCampaignActivitiesSinceDateByDate')
            ->with($expectedCampaignOriginId, $expectedDate->format(\DateTime::ISO8601))
            ->will($this->returnValueMap(
                [
                    [
                        $expectedCampaignOriginId,
                        $expectedDate->format(\DateTime::ISO8601),
                        1,
                        0,
                        $items
                    ],
                    [
                        $expectedCampaignOriginId,
                        $expectedDate->format(\DateTime::ISO8601),
                        1,
                        1,
                        new ApiCampaignContactSummaryList()
                    ],
                ]
            ));
        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[ActivityContactIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $this->assertSame($expectedActivityContactArray, $item);
        }
    }

    public function testIteratorInitFalse()
    {
        $resource = $this->createMock('DotMailer\Api\Resources\IResources');
        $expectedCampaignOriginId = 15662;
        $expectedDate = new \DateTime();
        $iterator = new ActivityContactIterator($resource, $expectedCampaignOriginId, false, $expectedDate);
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactSummaryList();
        $expectedActivity = new ApiCampaignContactSummary();
        $expectedActivity->contactId = 2;
        $items[] = $expectedActivity;
        $resource->expects($this->any())
            ->method('GetCampaignActivities')
            ->with($expectedCampaignOriginId)
            ->will($this->returnValueMap(
                [
                    [
                        $expectedCampaignOriginId,
                        1,
                        0,
                        $items
                    ],
                    [
                        $expectedCampaignOriginId,
                        1,
                        1,
                        new ApiCampaignContactSummaryList()
                    ],
                ]
            ));
        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[ActivityContactIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $this->assertSame($expectedActivityContactArray, $item);
        }
    }
}
