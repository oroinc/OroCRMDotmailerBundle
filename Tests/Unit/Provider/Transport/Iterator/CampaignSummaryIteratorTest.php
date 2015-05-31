<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiCampaignSummary;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignSummaryIterator;

class CampaignSummaryIteratorTest extends \PHPUnit_Framework_TestCase
{

    public function testIterator()
    {
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $firstCampaign = $this->getMock('OroCRM\Bundle\DotmailerBundle\Entity\Campaign');
        $firstCampaign->expects($this->once())
            ->method('getOriginId')
            ->will($this->returnValue($firstCampaignOriginId = 42));
        $firstCampaign->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($firstCampaignId = 1));

        $secondCampaign = $this->getMock('OroCRM\Bundle\DotmailerBundle\Entity\Campaign');
        $secondCampaign->expects($this->once())
            ->method('getOriginId')
            ->will($this->returnValue($secondCampaignOriginId = 28));
        $secondCampaign->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($secondCampaignId = 2));

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
            ->will(
                $this->returnValueMap(
                    [
                        [$firstCampaignOriginId, $expectedApiCampaigns[0]],
                        [$secondCampaignOriginId, $expectedApiCampaigns[1]],
                    ]
                )
            );

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
