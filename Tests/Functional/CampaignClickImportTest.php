<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactClickList;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class CampaignClickImportTest extends AbstractImportExportTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadActivityData::class, LoadStatusData::class]);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testImport(array $expected, array $clickList)
    {
        $entity = new ApiCampaignContactClickList();
        foreach ($clickList as $listItem) {
            $entity[] = $listItem;
        }

        $firstCampaignId = 15662;
        $secondCampaignId = 15666;

        $this->resource->expects($this->exactly(2))
            ->method('GetCampaignClicks')
            ->withConsecutive([$firstCampaignId], [$secondCampaignId])
            ->willReturnOnConsecutiveCalls(new ApiCampaignContactClickList(), $entity);

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
        $enumProvider = $this->getContainer()->get('oro_entity_extend.enum_options_provider');
        $clickType = $enumProvider->getEnumOptionByCode(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_CLICK
        );

        $queryBuilder = $marketingActivityRepository->createQueryBuilder('ma');
        foreach ($expected as $activityExpected) {
            $queryBuilder
                ->andWhere('ma.details = :details')
                ->andWhere('ma.actionDate = :actionDate')
                ->andWhere('ma.entityId = :entityId')
                ->andWhere('ma.entityClass = :entityClass')
                ->andWhere('ma.campaign = :campaign')
                ->andWhere('ma.relatedCampaignId = :relatedCampaignId')
                ->andWhere("JSON_EXTRACT(ma.serialized_data, 'type') = :clickType")
                ->setParameter('clickType', $clickType->getId())
                ->setParameter('details', $activityExpected['details'])
                ->setParameter('actionDate', $activityExpected['actionDate'])
                ->setParameter('entityId', $this->getReference($activityExpected['contact'])->getId())
                ->setParameter('entityClass', Contact::class)
                ->setParameter('campaign', $this->getReference('oro_dotmailer.marketing_campaign.second'))
                ->setParameter(
                    'relatedCampaignId',
                    $this->getReference('oro_dotmailer.email_campaign.second')->getId()
                );

            $clickActivities = $queryBuilder->getQuery()->getResult();

            $this->assertCount(1, $clickActivities);
        }
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'     => [
                    [
                        'details' => 'http://example.com/page3',
                        'actionDate' => new \DateTime('2013-01-03T20:05:00'),
                        'contact' => 'oro_dotmailer.orocrm_contact.nick.case'
                    ],
                    [
                        'details' => 'http://example.com/page2',
                        'actionDate' => new \DateTime('2013-01-02T17:52:00'),
                        'contact' => 'oro_dotmailer.orocrm_contact.mike.case'
                    ],
                ],
                'clickList' => [
                    [
                        'contactId' => 222, //oro_dotmailer.contact.nick_case.second_channel
                        'email' => 'nick.case@example.com',
                        'url' => 'http://example.com/page3',
                        'ipAddress' => '192.168.237.24',
                        'userAgent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)',
                        'dateClicked' => '2013-01-03T20:05:00',
                        'keyword' => 'example'
                    ],
                    [
                        'contactId' => 223, //oro_dotmailer.contact.mike_case.second_channel
                        'email' => 'mike.case@example.com',
                        'url' => 'http://example.com/page2',
                        'ipAddress' => '192.168.237.24',
                        'userAgent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)',
                        'dateClicked' => '2013-01-02T17:52:00',
                        'keyword' => 'example'
                    ],
                ]
            ]
        ];
    }
}
