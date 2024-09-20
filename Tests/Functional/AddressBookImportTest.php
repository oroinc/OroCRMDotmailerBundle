<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBookList;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AddressBookImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadChannelData::class]);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testImport(array $expected, array $addressBookList)
    {
        $entity = new ApiAddressBookList();
        foreach ($addressBookList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBooks')
            ->willReturn($entity);
        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            AddressBookConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);

        $this->assertTrue($result, "Job Failed with output:\n $log");

        $addressBookRepository = $this->managerRegistry->getRepository(AddressBook::class);

        foreach ($expected as $addressBook) {
            $queryBuilder = $addressBookRepository->createQueryBuilder('ab');
            $queryBuilder
                ->andWhere('ab.originId = :originId')
                ->andWhere('ab.channel = :channel')
                ->andWhere('ab.name = :name')
                ->andWhere('ab.contactCount = :contactCount')
                ->andWhere("JSON_EXTRACT(ab.serialized_data, 'visibility') = :visibility")
                ->setParameter('originId', $addressBook['originId'])
                ->setParameter('channel', $channel)
                ->setParameter('name', $addressBook['name'])
                ->setParameter('contactCount', $addressBook['contactCount'])
                ->setParameter(
                    'visibility',
                    ExtendHelper::buildEnumOptionId('dm_ab_visibility', $addressBook['visibility'])
                );

            $addressBook = $queryBuilder->getQuery()->getResult();

            $this->assertCount(1, $addressBook);
        }
    }

    public function importDataProvider(): array
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
