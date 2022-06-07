<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;

class ActivityContactImportTest extends AbstractImportExportTestCase
{
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
            ->willReturn($entity);
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

        $activityContactRepository = $this->managerRegistry->getRepository(Activity::class);

        foreach ($expected as $activityExpected) {
            $searchCriteria = [
                'email'                => $activityExpected['email'],
                'numOpens'             => $activityExpected['numOpens'],
                'numPageViews'         => $activityExpected['numPageViews'],
                'numClicks'            => $activityExpected['numClicks'],
                'numForwards'          => $activityExpected['numForwards'],
                'numEstimatedForwards' => $activityExpected['numEstimatedForwards'],
                'numReplies'           => $activityExpected['numReplies'],
                'dateSent'             => $activityExpected['dateSent'],
                'dateFirstOpened'      => $activityExpected['dateFirstOpened'],
                'dateLastOpened'       => $activityExpected['dateLastOpened'],
                'firstOpenIp'          => $activityExpected['firstOpenIp'],
                'unsubscribed'         => $activityExpected['unsubscribed'],
                'softBounced'          => $activityExpected['softBounced'],
                'hardBounced'          => $activityExpected['hardBounced'],
                'contact'              => $this->getReference($activityExpected['contactid']),
                'channel'              => $channel,
            ];

            $activitiesEntities = $activityContactRepository->findBy($searchCriteria);

            $this->assertCount(
                2,
                $activitiesEntities,
                sprintf(
                    'Incorrect activities count for %s.',
                    $activityExpected['email']
                )
            );
        }
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'     => [
                    [
                        'email'                => 'alex.case@example.com',
                        'numOpens'             => 3,
                        'numPageViews'         => 0,
                        'numClicks'            => 0,
                        'numForwards'          => 0,
                        'numEstimatedForwards' => 2,
                        'numReplies'           => 0,
                        'dateSent'             => new \DateTime('2015-04-15T13:48:33.013Z'),
                        'dateFirstOpened'      => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'dateLastOpened'       => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'firstOpenIp'          => '61.249.92.173',
                        'unsubscribed'         => false,
                        'softBounced'          => false,
                        'hardBounced'          => false,
                        'contactid'            => 'oro_dotmailer.contact.alex_case.second_channel',
                    ],
                    [
                        'email'                => 'first@mail.com',
                        'numOpens'             => 3,
                        'numPageViews'         => 0,
                        'numClicks'            => 0,
                        'numForwards'          => 0,
                        'numEstimatedForwards' => 2,
                        'numReplies'           => 0,
                        'dateSent'             => new \DateTime('2015-04-15T13:48:33.013Z'),
                        'dateFirstOpened'      => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'dateLastOpened'       => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'firstOpenIp'          => '61.249.92.173',
                        'unsubscribed'         => false,
                        'softBounced'          => false,
                        'hardBounced'          => false,
                        'contactid'            => 'oro_dotmailer.contact.first',
                    ],
                ],
                'activityList' => [
                    [
                        'email'                => 'alex.case@example.com',
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
                        'hardbounced'          => 'false',
                        'contactid'            => 147,
                    ],
                    [
                        'email'                => 'first@mail.com',
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
                        'hardbounced'          => 'false',
                        'contactid'            => 42,
                    ],
                    [
                        'email'                => 'first@mail.com',
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
                        'hardbounced'          => 'false',
                        'contactid'            => 42,
                    ],
                ]
            ]
        ];
    }
}
