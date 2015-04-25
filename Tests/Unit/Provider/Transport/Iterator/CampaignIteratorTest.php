<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiCampaign;
use DotMailer\Api\DataTypes\ApiCampaignList;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;

class CampaignIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $expectedAddressBookOriginId = 42;
        $iterator = new CampaignIterator($resource, $expectedAddressBookOriginId);
        $iterator->setBatchSize(1);
        $items = new ApiCampaignList();
        $expectedCampaign = new ApiCampaign();
        $expectedCampaign->id = 2;
        $items[] = $expectedCampaign;
        $resource->expects($this->exactly(2))
            ->method('GetAddressBookCampaigns')
            ->with($expectedAddressBookOriginId)
            ->will($this->returnValueMap(
                [
                    [$expectedAddressBookOriginId, 1, 0, $items],
                    [$expectedAddressBookOriginId, 1, 1, new ApiCampaignList()],
                ]
            ));
        foreach ($iterator as $item) {
            $expectedCampaignArray = $expectedCampaign->toArray();
            $expectedCampaignArray[CampaignIterator::ADDRESS_BOOK_KEY] = $expectedAddressBookOriginId;
            $this->assertSame($expectedCampaignArray, $item);
        }
    }
}
