<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaign;
use DotMailer\Api\DataTypes\ApiCampaignList;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData;

class RemoveCampaignsImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAddressBookData::class, LoadCampaignData::class]);
    }

    public function testImport()
    {
        $entity = new ApiCampaignList();
        $entity[] = new ApiCampaign(
            [
                'id' => 15663,
                'name' => 'NewsLetter',
                'subject' => 'News Letter',
                'fromname' => 'CityBeach',
                'fromaddress' => [
                    'id' => 6141,
                    'email' => 'Arbitbet@dotmailer-email.com',
                ],
                'htmlcontent' => 'null',
                'plaintextcontent' => 'null',
                'replyaction' => 'Webmail',
                'replytoaddress' => '',
                'issplittest' => 'false',
                'status' => 'Unsent'
            ]
        );

        $this->resource->expects($this->any())
            ->method('GetAddressBookCampaigns')
            ->willReturn($entity);

        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $campaign = $this->managerRegistry->getRepository(Campaign::class)
            ->findOneBy(['originId' => '15663', 'channel' => $channel]);
        $this->assertFalse($campaign->isDeleted());

        $campaign = $this->managerRegistry->getRepository(Campaign::class)
            ->findOneBy(['originId' => '15664', 'channel' => $channel]);
        $this->assertTrue($campaign->isDeleted());
    }
}
