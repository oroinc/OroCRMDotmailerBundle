<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport;

use DotMailer\Api\DataTypes\ApiAccount;
use DotMailer\Api\DataTypes\ApiCampaignContactClickList;
use DotMailer\Api\DataTypes\ApiCampaignContactOpenList;
use DotMailer\Api\Rest\IClient;
use Oro\Bundle\DotmailerBundle\Provider\Transport\AdditionalResource;

class AdditionalResourceTest extends \PHPUnit\Framework\TestCase
{
    /** @var IClient|\PHPUnit\Framework\MockObject\MockObject */
    private $restClient;

    /** @var AdditionalResource */
    private $additionalResource;

    protected function setUp(): void
    {
        $this->restClient = $this->createMock(IClient::class);
        $this->additionalResource = new AdditionalResource($this->restClient);
    }

    public function testGetAccountInfo()
    {
        $this->restClient->expects($this->once())
            ->method('execute')
            ->with(['account-info', 'GET', null]);
        $result = $this->additionalResource->getAccountInfo();
        $this->assertInstanceOf(ApiAccount::class, $result);
    }

    public function testGetCampaignClicksSinceDateByDate()
    {
        $campaignId = '1';
        $dateTime = '2016-01-01';
        $select = '100';
        $skip = '10';
        $expectedRequest = 'campaigns/1/clicks/since-date/2016-01-01?select=100&skip=10';
        $this->restClient->expects($this->once())
            ->method('execute')
            ->with([$expectedRequest, 'GET', null]);
        $result = $this->additionalResource->getCampaignClicksSinceDateByDate($campaignId, $dateTime, $select, $skip);
        $this->assertInstanceOf(ApiCampaignContactClickList::class, $result);
    }

    public function testGetCampaignOpensSinceDateByDate()
    {
        $campaignId = '1';
        $dateTime = '2016-01-01';
        $select = '100';
        $skip = '10';
        $expectedRequest = 'campaigns/1/opens/since-date/2016-01-01?select=100&skip=10';
        $this->restClient->expects($this->once())
            ->method('execute')
            ->with([$expectedRequest, 'GET', null]);
        $result = $this->additionalResource->getCampaignOpensSinceDateByDate($campaignId, $dateTime, $select, $skip);
        $this->assertInstanceOf(ApiCampaignContactOpenList::class, $result);
    }
}
