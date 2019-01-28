<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactOpenList;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignOpenConnector;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class CampaignOpenImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
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
        $entity = new ApiCampaignContactOpenList();
        foreach ($clickList as $listItem) {
            $entity[] = $listItem;
        }

        $firstCampaignId = 15662; //oro_dotmailer.campaign.first
        $secondCampaignId = 15666; //oro_dotmailer.campaign.fifth

        $this->resource->expects($this->at(0))
            ->method('GetCampaignOpens')
            ->with($firstCampaignId)
            ->will($this->returnValue($entity));

        $this->resource->expects($this->at(1))
            ->method('GetCampaignOpens')
            ->with($secondCampaignId)
            ->will($this->returnValue(new ApiCampaignContactOpenList()));

        $channel = $this->getReference('oro_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignOpenConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $marketingActivityRepository = $this->managerRegistry->getRepository(MarketingActivity::class);
        $enumProvider = $this->getContainer()->get('oro_entity_extend.enum_value_provider');
        $openType = $enumProvider->getEnumValueByCode(MarketingActivity::TYPE_ENUM_CODE, MarketingActivity::TYPE_OPEN);

        foreach ($expected as $activityExpected) {
            $searchCriteria = [
                'actionDate' => $activityExpected['actionDate'],
                'type' => $openType,
                'entityId' => $this->getReference($activityExpected['contact'])->getId(),
                'entityClass' => 'Oro\Bundle\ContactBundle\Entity\Contact',
                'campaign' => $this->getReference('oro_dotmailer.marketing_campaign.first'),
                'relatedCampaignId' => $this->getReference('oro_dotmailer.email_campaign.first')->getId()
            ];

            $openActivities = $marketingActivityRepository->findBy($searchCriteria);

            $this->assertCount(1, $openActivities);
        }
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'     => [
                    [
                        "actionDate" => new \DateTime("2013-01-03T20:05:00"),
                        'contact' => 'oro_dotmailer.orocrm_contact.nick.case'
                    ],
                    [
                        "actionDate" => new \DateTime("2013-01-02T17:52:00"),
                        'contact' => 'oro_dotmailer.orocrm_contact.mike.case'
                    ],
                ],
                'clickList' => [
                    [
                        "contactId" => 222, //oro_dotmailer.contact.nick_case.second_channel
                        "email" => "nick.case@example.com",
                        "ipAddress" => "192.168.237.24",
                        "userAgent" => "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)",
                        "isHtml" => false,
                        "isForward" => false,
                        "dateOpened" => "2013-01-03T20:05:00",
                    ],
                    [
                        "contactId" => 223, //oro_dotmailer.contact.mike_case.second_channel
                        "email" => "mike.case@example.com",
                        "ipAddress" => "192.168.237.24",
                        "userAgent" => "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)",
                        "isHtml" => false,
                        "isForward" => false,
                        "dateOpened" => "2013-01-02T17:52:00",
                    ],
                ]
            ]
        ];
    }
}
