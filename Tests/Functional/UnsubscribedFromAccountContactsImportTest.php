<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class UnsubscribedFromAccountContactsImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class, LoadStatusData::class]);
    }

    /**
     * @dataProvider importUnsubscribedFromAccountContactsDataProvider
     */
    public function testUnsubscribedFromAccountContactsImport(array $expected, array $apiContactSuppressionList)
    {
        $entity = new ApiContactSuppressionList();
        foreach ($apiContactSuppressionList as $listItem) {
            $entity[] = $listItem;
        }
        $this->resource->expects($this->any())
            ->method('GetContactsSuppressedSinceDate')
            ->willReturn($entity);

        $this->resource->expects($this->any())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->willReturn(new ApiContactSuppressionList());

        $channel = $this->getReference('oro_dotmailer.channel.third');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            UnsubscribedContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $contactRepository = $this->managerRegistry->getRepository(Contact::class);
        $statusRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cnt_status')
        );

        foreach ($expected as $expectedContact) {
            $expectedStatus = $statusRepository->find($expectedContact['status']);
            $searchCriteria = [
                'originId' => $expectedContact['originId'],
                'status'   => $expectedStatus,
                'channel'  => $this->getReference($expectedContact['channel'])
            ];

            $actualContacts = $contactRepository->findBy($searchCriteria);
            $this->assertCount(1, $actualContacts);
            /** @var Contact $actualContact */
            $actualContact = $actualContacts[0];

            $actualAddressBooks = $actualContact->getAddressBookContacts();
            foreach ($actualAddressBooks as $actualAddressBook) {
                $this->assertEquals($expectedContact['unsubscribedDate'], $actualAddressBook->getUnsubscribedDate());
                $this->assertEquals($expectedStatus, $actualAddressBook->getStatus());
            }
            $this->assertEquals($expectedContact['unsubscribedDate'], $actualContact->getUnsubscribedDate());
        }
    }

    public function importUnsubscribedFromAccountContactsDataProvider(): array
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'     => 42,
                        'channel'      => 'oro_dotmailer.channel.third',
                        'status'       => ApiContactStatuses::UNSUBSCRIBED,
                        'addressBooks' => ['oro_dotmailer.address_book.fourth'],
                        'unsubscribedDate' => new \DateTime('2015-10-10', new \DateTimeZone('UTC'))
                    ]
                ],
                'apiContactSuppressionList' => [
                    [
                        'suppressedContact' => [
                            'Id'         => 42,
                            'Email'      => 'second@mail.com',
                            'EmailType'  => ApiContactEmailTypes::PLAIN_TEXT,
                            'DataFields' => [],
                            'Status'     => ApiContactStatuses::SUBSCRIBED
                        ],
                        'dateRemoved'       => '2015-10-10T00:00:00z',
                        'reason'            => ApiContactStatuses::UNSUBSCRIBED
                    ]
                ]
            ]
        ];
    }
}
