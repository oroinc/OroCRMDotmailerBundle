<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;

/**
 * @dbIsolation
 */
class CampaignSummaryUpdateTest extends AbstractImportExportTestCase
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
        /** @var Campaign $expectedCampaign */
        $expectedCampaign = $this->getReference('orocrm_dotmailer.campaign.first');

        $this->resource->expects($this->any())
            ->method('GetCampaignSummary')
            ->willReturnMap(
                [
                    [
                        $expectedCampaign->getOriginId(),
                        $entity
                    ]
                ]
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

        /** @var CampaignSummary $actual */
        $actual = $summaryEntities[0];
        $this->assertEquals($expectedCampaign->getId(), $actual->getCampaign()->getId());

        $this->assertNotNull($expectedCampaign->getEmailCampaign());
        $this->assertEquals($expectedCampaign->getEmailCampaign()->getSentAt(), $expected['sentAt']);
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
                    'id' => 15662,
                    'sentAt' => date_create_from_format('Y-m-d H:i:s', '2015-03-02 10:02:03', new \DateTimeZone('UTC'))
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
                    'dateSent' => '2015-03-02T10:02:03z'
                ]
            ]
        ];
    }
}
