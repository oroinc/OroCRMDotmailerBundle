<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;

use Oro\Bundle\IntegrationBundle\Command\SyncCommand;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class CampaignSummaryUpdateTest extends AbstractImportExportTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignSummaryData',
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
        $campaignSummaryRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:CampaignSummary');
        $summaryEntities = $campaignSummaryRepository->findAll();
        $this->assertCount(1, $summaryEntities);
        $summaryEntities = null;

        $entity = new ApiCampaignSummary($summary);

        $this->resource->expects($this->any())
            ->method('GetCampaignSummary')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.second');

        $processor = $this->getContainer()->get(self::SYNC_PROCESSOR);
        $result = $processor->process($channel, CampaignSummaryConnector::TYPE);

        $this->assertTrue($result);

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
        $this->assertCount(1, $summaryEntities);

        $summaryEntities = $campaignSummaryRepository->findAll();
        $this->assertCount(1, $summaryEntities);
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
