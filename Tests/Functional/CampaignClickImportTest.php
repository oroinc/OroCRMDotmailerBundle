<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactClickList;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class CampaignClickImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData',
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $clickList
     */
    public function testImport($expected, $clickList)
    {
        $entity = new ApiCampaignContactClickList();
        foreach ($clickList as $listItem) {
            $entity[] = $listItem;
        }

        $firstCampaignId = 15662;
        $secondCampaignId = 15666;

        $this->resource->expects($this->at(0))
            ->method('GetCampaignClicks')
            ->with($firstCampaignId)
            ->will($this->returnValue(new ApiCampaignContactClickList()));

        $this->resource->expects($this->at(1))
            ->method('GetCampaignClicks')
            ->with($secondCampaignId)
            ->will($this->returnValue($entity));

        $channel = $this->getReference('oro_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignClickConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $marketingActivityRepository = $this->managerRegistry->getRepository(MarketingActivity::class);
        $enumProvider = $this->getContainer()->get('oro_entity_extend.enum_value_provider');
        $clickType = $enumProvider->getEnumValueByCode(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_CLICK
        );

        foreach ($expected as $activityExpected) {
            $searchCriteria = [
                'details' => $activityExpected['details'],
                'actionDate' => $activityExpected['actionDate'],
                'type' => $clickType,
                'entityId' => $this->getReference($activityExpected['contact'])->getId(),
                'entityClass' => 'Oro\Bundle\ContactBundle\Entity\Contact',
                'campaign' => $this->getReference('oro_dotmailer.marketing_campaign.second'),
                'relatedCampaignId' => $this->getReference('oro_dotmailer.email_campaign.second')->getId()
            ];

            $clickActivities = $marketingActivityRepository->findBy($searchCriteria);

            $this->assertCount(1, $clickActivities);
        }
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'     => [
                    [
                        "details" => "http://example.com/page3",
                        "actionDate" => new \DateTime("2013-01-03T20:05:00"),
                        'contact' => 'oro_dotmailer.orocrm_contact.nick.case'
                    ],
                    [
                        "details" => "http://example.com/page2",
                        "actionDate" => new \DateTime("2013-01-02T17:52:00"),
                        'contact' => 'oro_dotmailer.orocrm_contact.mike.case'
                    ],
                ],
                'clickList' => [
                    [
                        "contactId" => 222, //oro_dotmailer.contact.nick_case.second_channel
                        "email" => "nick.case@example.com",
                        "url" => "http://example.com/page3",
                        "ipAddress" => "192.168.237.24",
                        "userAgent" => "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)",
                        "dateClicked" => "2013-01-03T20:05:00",
                        "keyword" => "example"
                    ],
                    [
                        "contactId" => 223, //oro_dotmailer.contact.mike_case.second_channel
                        "email" => "mike.case@example.com",
                        "url" => "http://example.com/page2",
                        "ipAddress" => "192.168.237.24",
                        "userAgent" => "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)",
                        "dateClicked" => "2013-01-02T17:52:00",
                        "keyword" => "example"
                    ],
                ]
            ]
        ];
    }
}
