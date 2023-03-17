<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiCampaignSummary;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignSummaryIterator;

class CampaignSummaryIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $resource = $this->createMock(IResources::class);
        $firstCampaign = $this->createMock(Campaign::class);
        $firstCampaign->expects($this->once())
            ->method('getOriginId')
            ->willReturn($firstCampaignOriginId = 42);
        $firstCampaign->expects($this->once())
            ->method('getId')
            ->willReturn($firstCampaignId = 1);

        $secondCampaign = $this->createMock(Campaign::class);
        $secondCampaign->expects($this->once())
            ->method('getOriginId')
            ->willReturn($secondCampaignOriginId = 28);
        $secondCampaign->expects($this->once())
            ->method('getId')
            ->willReturn($secondCampaignId = 2);

        $iterator = new CampaignSummaryIterator(
            $resource,
            [
                $firstCampaign,
                $secondCampaign
            ]
        );

        $expectedApiCampaigns = [
            new ApiCampaignSummary(['NumClicks' => 2]),
            new ApiCampaignSummary()
        ];

        $resource->expects($this->exactly(2))
            ->method('GetCampaignSummary')
            ->willReturnMap([
                [$firstCampaignOriginId, $expectedApiCampaigns[0]],
                [$secondCampaignOriginId, $expectedApiCampaigns[1]],
            ]);

        $iterator->rewind();

        $actual = $iterator->current();
        $expectedCampaignArray = current($expectedApiCampaigns)->toArray();
        $expectedCampaignArray[CampaignSummaryIterator::CAMPAIGN_KEY] = $firstCampaignId;
        $this->assertEquals($expectedCampaignArray, $actual);

        $iterator->next();
        next($expectedApiCampaigns);

        $actual = $iterator->current();
        $expectedCampaignArray = current($expectedApiCampaigns)->toArray();
        $expectedCampaignArray[CampaignSummaryIterator::CAMPAIGN_KEY] = $secondCampaignId;
        $this->assertEquals($expectedCampaignArray, $actual);

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
