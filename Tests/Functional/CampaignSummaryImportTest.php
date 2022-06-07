<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;
use Oro\Bundle\DotmailerBundle\Entity\CampaignSummary;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData;

class CampaignSummaryImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCampaignData::class]);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testImport(array $expected, array $summary)
    {
        $entity = new ApiCampaignSummary($summary);

        /**
         * Necessary to be string for PostgreSQL
         */
        $expectedCampaignOriginId = '15662';
        $secondCampaignOriginId = '15666';
        $this->resource->expects($this->exactly(2))
            ->method('GetCampaignSummary')
            ->willReturnMap([
                [$expectedCampaignOriginId, $entity],
                [$secondCampaignOriginId, null],
            ]);
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

        $campaignSummaryRepository = $this->managerRegistry->getRepository(CampaignSummary::class);

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

    public function importDataProvider(): array
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
