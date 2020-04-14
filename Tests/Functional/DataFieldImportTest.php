<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiDataFieldList;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class DataFieldImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData'
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $dataFieldList
     */
    public function testImport($expected, $dataFieldList)
    {
        $entity = new ApiDataFieldList();
        foreach ($dataFieldList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetDataFields')
            ->will($this->returnValue($entity));
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

        $dataFieldRepository = $this->managerRegistry->getRepository('OroDotmailerBundle:DataField');
        $visibilityRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_df_visibility')
        );
        $typeRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_df_type')
        );

        foreach ($expected as $dataField) {
            $searchCriteria = [
                'channel' => $channel,
                'name' => $dataField['name'],
                'visibility' => $visibilityRepository->find($dataField['visibility']),
                'type' => $typeRepository->find($dataField['type'])
            ];

            $dataField = $dataFieldRepository->findBy($searchCriteria);

            $this->assertCount(1, $dataField);
        }
        //check that connector was skipped during second import and last sync date was not updated
        $lastSyncDate = $this->getLastSyncDate($channel);
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            DataFieldConnector::TYPE,
            [],
            $jobLog
        );
        $lastSyncDateAfter = $this->getLastSyncDate($channel);

        $this->assertTrue($lastSyncDate == $lastSyncDateAfter);
    }

    /**
     * @return array
     */
    public function importDataProvider()
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

    /**
     * @param $channel
     * @return \DateTime
     */
    protected function getLastSyncDate($channel)
    {
        $status = $this->managerRegistry->getRepository('OroIntegrationBundle:Channel')
            ->getLastStatusForConnector($channel, DataFieldConnector::TYPE, Status::STATUS_COMPLETED);
        $date = null;
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
