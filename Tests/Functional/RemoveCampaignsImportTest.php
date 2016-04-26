<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaign;
use DotMailer\Api\DataTypes\ApiCampaignList;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;

/**
 * @dbIsolation
 */
class RemoveCampaignsImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData'
            ]
        );
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
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $campaign = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Campaign')
            ->findOneBy(['originId' => '15663', 'channel' => $channel]);
        $this->assertFalse($campaign->isDeleted());

        $campaign = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Campaign')
            ->findOneBy(['originId' => '15664', 'channel' => $channel]);
        $this->assertTrue($campaign->isDeleted());
    }
}
