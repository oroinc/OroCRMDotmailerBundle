<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBookList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;

/**
 * @dbIsolation
 */
class AddressBookImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData'
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $addressBookList
     */
    public function testImport($expected, $addressBookList)
    {
        $entity = new ApiAddressBookList();
        foreach ($addressBookList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBooks')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            AddressBookConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $addressBookRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBook');
        $visibilityRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_ab_visibility')
        );

        foreach ($expected as $addressBook) {
            $searchCriteria = [
                'originId' => $addressBook['originId'],
                'channel' => $channel,
                'name' => $addressBook['name'],
                'contactCount'=> $addressBook['contactCount'],
                'visibility' => $visibilityRepository->find($addressBook['visibility'])
            ];

            $addressBook = $addressBookRepository->findBy($searchCriteria);

            $this->assertCount(1, $addressBook);
        }
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'     => 11,
                        'name'         => 'test1',
                        'contactCount' => 23,
                        'visibility'   => 'Private'
                    ],
                    [
                        'originId'     => 23,
                        'name'         => 'test2',
                        'contactCount' => 43,
                        'visibility'   => 'Public'
                    ],
                    [
                        'originId'     => 45,
                        'name'         => 'test3',
                        'contactCount' => 1,
                        'visibility'   => 'Private'
                    ],
                    [
                        'originId'     => 67,
                        'name'         => 'test4',
                        'contactCount' => 2,
                        'visibility'   => 'NotAvailableInThisVersion'
                    ],
                ],
                'addressBookList' => [
                    [
                        'id'         => 11,
                        'name'       => 'test1',
                        'contacts'   => 23,
                        'visibility' => 'Private'
                    ],
                    [
                        'id'         => 23,
                        'name'       => 'test2',
                        'contacts'   => 43,
                        'visibility' => 'Public'
                    ],
                    [
                        'id'         => 45,
                        'name'       => 'test3',
                        'contacts'   => 1,
                        'visibility' => 'Private'
                    ],
                    [
                        'id'         => 67,
                        'name'       => 'test4',
                        'contacts'   => 2,
                        'visibility' => 'NotAvailableInThisVersion'
                    ]
                ]
            ]
        ];
    }
}
