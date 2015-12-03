<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;

/**
 * @dbIsolation
 */
class CampaignSummaryImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData',
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $summary
     */
    public function testImport($expected, $summary)
    {
        $entity = new ApiCampaignSummary($summary);

        /**
         * Necessary to be string for PostgreSQL
         */
        $expectedCampaignOriginId = '15662';
        $secondCampaignOriginId = '15666';
        $this->resource->expects($this->exactly(2))
            ->method('GetCampaignSummary')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedCampaignOriginId, $entity],
                        [$secondCampaignOriginId, null],
                    ]
                )
            );
        $channel = $this->getReference('orocrm_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignSummaryConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $campaignSummaryRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:CampaignSummary');

        $searchCriteria = [
            'numUniqueOpens'      => $expected['numUniqueOpens'],
            'numUniqueTextOpens'  => $expected['numUniqueTextOpens'],
            'numTotalUniqueOpens' => $expected['numTotalUniqueOpens'],
            'numOpens'            => $expected['numOpens'],
            'numTextOpens'        => $expected['numTextOpens'],
            'numTotalOpens'       => $expected['numTotalOpens'],
            'numClicks'           => $expected['numClicks'],
            'numTextClicks'       => $expected['numTextClicks'],
            'numTotalClicks'      => $expected['numTotalClicks'],
        ];

        $summaryEntities = $campaignSummaryRepository->findBy($searchCriteria);

        $this->assertCount(1, $summaryEntities);
    }

    public function importDataProvider()
    {
        return [
            [
                'expected' => [
                    'numUniqueOpens'      => 5,
                    'numUniqueTextOpens'  => 5,
                    'numTotalUniqueOpens' => 5,
                    'numOpens'            => 5,
                    'numTextOpens'        => 5,
                    'numTotalOpens'       => 5,
                    'numClicks'           => 5,
                    'numTextClicks'       => 5,
                    'numTotalClicks'      => 5,
                ],
                'summary'  => [
                    'numUniqueOpens'      => 5,
                    'numUniqueTextOpens'  => 5,
                    'numTotalUniqueOpens' => 5,
                    'numOpens'            => 5,
                    'numTextOpens'        => 5,
                    'numTotalOpens'       => 5,
                    'numClicks'           => 5,
                    'numTextClicks'       => 5,
                    'numTotalClicks'      => 5,
                ]
            ]
        ];
    }
}
