<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;

/**
 * @dbIsolation
 */
class ActivityContactImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData',
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $activityList
     */
    public function testImport($expected, $activityList)
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
            ->will($this->returnValue($entity));
        $this->resource->expects($this->once())
            ->method('GetCampaignActivities')
            ->with($secondCampaignId)
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ActivityContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $activityContactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Activity');

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

    public function importDataProvider()
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
                        'contactid'            => 'orocrm_dotmailer.contact.alex_case.second_channel',
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
                        'contactid'            => 'orocrm_dotmailer.contact.first',
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
