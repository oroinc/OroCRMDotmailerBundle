<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiDataFieldList;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class DataFieldImportTest extends AbstractImportExportTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadChannelData::class]);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testImport(array $expected, array $dataFieldList)
    {
        $entity = new ApiDataFieldList();
        foreach ($dataFieldList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetDataFields')
            ->willReturn($entity);
        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            DataFieldConnector::TYPE,
            [DataFieldConnector::FORCE_SYNC_FLAG => 1],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $dataFieldRepository = $this->managerRegistry->getRepository(DataField::class);

        foreach ($expected as $dataField) {
            $queryBuilder = $dataFieldRepository->createQueryBuilder('df');
            $queryBuilder
                ->andWhere('df.channel = :channel')
                ->andWhere('df.name = :name')
                ->andWhere("JSON_EXTRACT(df.serialized_data, 'visibility') = :visibility")
                ->andWhere("JSON_EXTRACT(df.serialized_data, 'type') = :type")
                ->setParameter('channel', $channel)
                ->setParameter('name', $dataField['name'])
                ->setParameter(
                    'visibility',
                    ExtendHelper::buildEnumOptionId('dm_df_visibility', $dataField['visibility'])
                )
                ->setParameter(
                    'type',
                    ExtendHelper::buildEnumOptionId('dm_df_type', $dataField['type'])
                );

            $dataFieldEntities = $queryBuilder->getQuery()->getResult();

            $this->assertCount(1, $dataFieldEntities);
        }
        //check that connector was skipped during second import and last sync date was not updated
        $lastSyncDate = $this->getLastSyncDate($channel);
        $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            DataFieldConnector::TYPE,
            [],
            $jobLog
        );
        $lastSyncDateAfter = $this->getLastSyncDate($channel);

        $this->assertEquals($lastSyncDate, $lastSyncDateAfter);
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'        => [
                    [
                        'name'         => 'test1',
                        'visibility'   => 'Private',
                        'type'         => 'Numeric',
                        'defaultValue' => '123'
                    ],
                    [
                        'name'         => 'test2',
                        'visibility'   => 'Public',
                        'type'         => 'String',
                        'defaultValue' => '32424'
                    ],
                    [
                        'name'         => 'test3',
                        'visibility'   => 'Public',
                        'type'         => 'Boolean',
                        'defaultValue' => ''
                    ],
                    [
                        'name'         => 'test4',
                        'visibility'   => 'Public',
                        'type'         => 'Date',
                        'defaultValue' => ''
                    ],
                ],
                'dataFieldList' => [
                    [
                        'Name'       => 'test1',
                        'Visibility' => 'Private',
                        'Type'       => 'Numeric',
                        'DefaultValue' => [
                            'value' => 123
                        ]
                    ],
                    [
                        'Name'       => 'test2',
                        'Visibility' => 'Public',
                        'Type'       => 'String',
                        'DefaultValue' => [
                            'value' => '32424'
                        ]
                    ],
                    [
                        'Name'       => 'test3',
                        'Visibility' => 'Public',
                        'Type'       => 'Boolean',
                        'DefaultValue' => [
                            'value' => 'null'
                        ]
                    ],
                    [
                        'Name'       => 'test4',
                        'Visibility' => 'Public',
                        'Type'       => 'Date',
                        'DefaultValue' => [
                            'value' => 'null'
                        ]
                    ],
                ]
            ]
        ];
    }

    private function getLastSyncDate(Integration $channel): \DateTime
    {
        $date = null;
        $status = $this->managerRegistry->getRepository(Integration::class)
            ->getLastStatusForConnector($channel, DataFieldConnector::TYPE, Status::STATUS_COMPLETED);
        if ($status) {
            $statusData = $status->getData();
            $date = new \DateTime(
                $statusData[AbstractDotmailerConnector::LAST_SYNC_DATE_KEY],
                new \DateTimeZone('UTC')
            );
        }

        return $date;
    }
}
