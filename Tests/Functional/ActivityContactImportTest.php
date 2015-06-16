<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ActivityContactImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
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

        $expectedCampaignOriginId = 15662;
        $this->resource->expects($this->once())
            ->method('GetCampaignActivities')
            ->with($expectedCampaignOriginId)
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.second');

        $processor = $this->getContainer()->get(self::SYNC_PROCESSOR);
        $result = $processor->process($channel, ActivityContactConnector::TYPE);

        $this->assertTrue($result);

        $activityContactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Activity');

        foreach ($expected as $activityExpected) {
            $searchCriteria = [
                'email' => $activityExpected['email'],
                'numOpens' => $activityExpected['numOpens'],
                'numPageViews' => $activityExpected['numPageViews'],
                'numClicks' => $activityExpected['numClicks'],
                'numForwards' => $activityExpected['numForwards'],
                'numEstimatedForwards' => $activityExpected['numEstimatedForwards'],
                'numReplies' => $activityExpected['numReplies'],
                'dateSent' => $activityExpected['dateSent'],
                'dateFirstOpened' => $activityExpected['dateFirstOpened'],
                'dateLastOpened' => $activityExpected['dateLastOpened'],
                'firstOpenIp' => $activityExpected['firstOpenIp'],
                'unsubscribed' => $activityExpected['unsubscribed'],
                'softBounced' => $activityExpected['softBounced'],
                'hardBounced' => $activityExpected['hardBounced'],
                'contact' => $this->getReference($activityExpected['contactid']),
                'channel' => $channel,
            ];

            $activitiesEntities = $activityContactRepository->findBy($searchCriteria);

            $this->assertCount(1, $activitiesEntities);
        }
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'email' => 'test@example.com',
                        'numOpens' => 3,
                        'numPageViews' => 0,
                        'numClicks' => 0,
                        'numForwards' => 0,
                        'numEstimatedForwards' => 2,
                        'numReplies' => 0,
                        'dateSent' => new \DateTime('2015-04-15T13:48:33.013Z'),
                        'dateFirstOpened' => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'dateLastOpened' => new \DateTime('2015-04-16T13:48:33.013Z'),
                        'firstOpenIp' => '61.249.92.173',
                        'unsubscribed' => false,
                        'softBounced' => false,
                        'hardBounced' => false,
                        'contactid' => 'orocrm_dotmailer.contact.first',
                    ],
                ],
                'activityList' => [
                    [
                        'email' => 'test@example.com',
                        'numopens' => 3,
                        'numpageviews' => 0,
                        'numclicks' => 0,
                        'numforwards' => 0,
                        'numestimatedforwards' => 2,
                        'numreplies' => 0,
                        'datesent' => '2015-04-15T13:48:33.013Z',
                        'datefirstopened' => '2015-04-16T13:48:33.013Z',
                        'datelastopened' => '2015-04-16T13:48:33.013Z',
                        'firstopenip' => '61.249.92.173',
                        'unsubscribed' => 'false',
                        'softbounced' => 'false',
                        'hardbounced' => 'false',
                        'contactid' => 42,
                    ],
                ]
            ]
        ];
    }
}
