<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class MarketingActivityImportTest extends AbstractImportExportTestCase
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
    public function testImport(array $expected, array $activityList)
    {
        $entity = new ApiCampaignContactSummaryList();
        foreach ($activityList as $listItem) {
            $entity[] = $listItem;
        }

        $firstCampaignId = 15662;
        $secondCampaignId = 15666;

        $this->resource->expects($this->once())
            ->method('GetCampaignActivitiesSinceDateByDate')
            ->with($firstCampaignId)
            ->willReturn($entity);
        $this->resource->expects($this->once())
            ->method('GetCampaignActivities')
            ->with($secondCampaignId)
            ->willReturn(new ApiCampaignContactSummaryList());

        $channel = $this->getReference('oro_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ActivityContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $marketingActivityRepository = $this->managerRegistry->getRepository(MarketingActivity::class);
        $enumProvider = $this->getContainer()->get('oro_entity_extend.enum_options_provider');
        $sendType = $enumProvider->getEnumOptionByCode(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_SEND
        );
        $unsubscribeType = $enumProvider->getEnumOptionByCode(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_UNSUBSCRIBE
        );
        $hardBounceType = $enumProvider->getEnumOptionByCode(
            MarketingActivity::TYPE_ENUM_CODE,
            MarketingActivity::TYPE_HARD_BOUNCE
        );
        $types = [
            'send' => $sendType,
            'unsubscribed' => $unsubscribeType,
            'hardBounced' => $hardBounceType,
        ];

        foreach ($expected as $activityExpected) {
            foreach ($types as $typeCode => $type) {
                if ($activityExpected[$typeCode]) {
                    $queryBuilder = $marketingActivityRepository->createQueryBuilder('ma');
                    $queryBuilder
                        ->andWhere("JSON_EXTRACT(ma.serialized_data, 'type') = :type")
                        ->andWhere('ma.entityId = :entityId')
                        ->andWhere('ma.entityClass = :entityClass')
                        ->andWhere('ma.campaign = :campaign')
                        ->andWhere('ma.relatedCampaignId = :relatedCampaignId')
                        ->setParameter('type', $type)
                        ->setParameter('entityId', $this->getReference($activityExpected['contact'])->getId())
                        ->setParameter('entityClass', Contact::class)
                        ->setParameter('campaign', $this->getReference('oro_dotmailer.marketing_campaign.first'))
                        ->setParameter(
                            'relatedCampaignId',
                            $this->getReference('oro_dotmailer.email_campaign.first')->getId()
                        );

                    $activities = $queryBuilder->getQuery()->getResult();
                    $this->assertCount(1, $activities);
                }
            }
        }
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'     => [
                    [
                        'email'        => 'nick.case@example.com',
                        'dateSent'     => new \DateTime('2015-04-15T13:48:33.013Z'),
                        'send'         => true,
                        'unsubscribed' => false,
                        'softBounced'  => false,
                        'hardBounced'  => true,
                        'contactid'    => 'oro_dotmailer.contact.nick_case.second_channel',
                        'contact'      => 'oro_dotmailer.orocrm_contact.nick.case'
                    ],
                    [
                        'email'        => 'mike.case@example.com',
                        'dateSent'     => new \DateTime('2015-04-15T13:48:33.013Z'),
                        'send'         => true,
                        'unsubscribed' => true,
                        'softBounced'  => false,
                        'hardBounced'  => false,
                        'contactid'    => 'oro_dotmailer.contact.mike_case.second_channel',
                        'contact'      => 'oro_dotmailer.orocrm_contact.mike.case'
                    ],
                ],
                'activityList' => [
                    [
                        'email'                => 'nick.case@example.com',
                        'numopens'             => 3,
                        'numpageviews'         => 0,
                        'numclicks'            => 0,
                        'numforwards'          => 0,
                        'numestimatedforwards' => 2,
                        'numreplies'           => 0,
                        'datesent'             => '2015-04-15T13:48:33.013Z',
                        'datefirstopened'      => '2015-04-16T13:48:33.013Z',
                        'datelastopened'       => '2015-04-16T13:48:33.013Z',
                        'firstopenip'          => '61.249.92.173',
                        'unsubscribed'         => 'false',
                        'softbounced'          => 'false',
                        'hardbounced'          => 'true',
                        'contactid'            => 222,
                    ],
                    [
                        'email'                => 'mike.case@example.com',
                        'numopens'             => 3,
                        'numpageviews'         => 0,
                        'numclicks'            => 0,
                        'numforwards'          => 0,
                        'numestimatedforwards' => 2,
                        'numreplies'           => 0,
                        'datesent'             => '2015-04-15T13:48:33.013Z',
                        'datefirstopened'      => '2015-04-16T13:48:33.013Z',
                        'datelastopened'       => '2015-04-16T13:48:33.013Z',
                        'firstopenip'          => '61.249.92.173',
                        'unsubscribed'         => 'true',
                        'softbounced'          => 'false',
                        'hardbounced'          => 'false',
                        'contactid'            => 223,
                    ],
                ]
            ]
        ];
    }
}
