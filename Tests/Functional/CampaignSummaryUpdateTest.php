<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;

class CampaignSummaryUpdateTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignSummaryData',
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
        $campaignSummaryRepository = $this->managerRegistry->getRepository('OroDotmailerBundle:CampaignSummary');
        $summaryEntities = $campaignSummaryRepository->findAll();
        $this->assertCount(1, $summaryEntities);
        $summaryEntities = null;

        $entity = new ApiCampaignSummary($summary);

        $this->resource->expects($this->any())
            ->method('GetCampaignSummary')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.second');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignSummaryConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $searchCriteria = [
            'numUniqueOpens' => $expected['numUniqueOpens'],
            'numUniqueTextOpens' => $expected['numUniqueTextOpens'],
            'numTotalUniqueOpens' => $expected['numTotalUniqueOpens'],
            'numOpens' => $expected['numOpens'],
            'numTextOpens' => $expected['numTextOpens'],
            'numTotalOpens' => $expected['numTotalOpens'],
            'numClicks' => $expected['numClicks'],
            'numTextClicks' => $expected['numTextClicks'],
            'numTotalClicks' => $expected['numTotalClicks'],
        ];

        $summaryEntities = $campaignSummaryRepository->findBy($searchCriteria);
        $this->assertCount(2, $summaryEntities);

        $summaryEntities = $campaignSummaryRepository->findAll();
        $this->assertCount(2, $summaryEntities);
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'        => [
                    'numUniqueOpens' => 15,
                    'numUniqueTextOpens' => 5,
                    'numTotalUniqueOpens' => 5,
                    'numOpens' => 15,
                    'numTextOpens' => 5,
                    'numTotalOpens' => 5,
                    'numClicks' => 5,
                    'numTextClicks' => 5,
                    'numTotalClicks' => 5,
                ],
                'summary' => [
                    'numUniqueOpens' => 15,
                    'numUniqueTextOpens' => 5,
                    'numTotalUniqueOpens' => 5,
                    'numOpens' => 15,
                    'numTextOpens' => 5,
                    'numTotalOpens' => 5,
                    'numClicks' => 5,
                    'numTextClicks' => 5,
                    'numTotalClicks' => 5,
                ]
            ]
        ];
    }
}
