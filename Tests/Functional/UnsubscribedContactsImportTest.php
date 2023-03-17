<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class UnsubscribedContactsImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class, LoadStatusData::class]);
    }

    /**
     * @dataProvider importUnsubscribedContactsDataProvider
     */
    public function testUnsubscribedContactsImport(array $expected, array $apiContactSuppressionLists)
    {
        $entityMap = [];
        foreach ($apiContactSuppressionLists as $addressBookId => $apiContactSuppressionList) {
            $entity = new ApiContactSuppressionList();
            foreach ($apiContactSuppressionList as $listItem) {
                $entity[] = $listItem;
            }
            $entityMap[$addressBookId] = $entity;
        }
        $this->resource->expects($this->any())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->willReturnCallback(function ($entityId) use ($entityMap) {
                return $entityMap[$entityId];
            });

        $this->resource->expects($this->any())
            ->method('GetContactsSuppressedSinceDate')
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
            $searchCriteria = [
                'originId' => $expectedContact['originId'],
                'status'   => $statusRepository->find($expectedContact['status']),
                'channel'  => $this->getReference($expectedContact['channel'])
            ];

            $actualContacts = $contactRepository->findBy($searchCriteria);
            $this->assertCount(1, $actualContacts);
            /** @var Contact $actualContact */
            $actualContact = $actualContacts[0];
            foreach ($expectedContact['subscribedAddressBooks'] as &$addressBook) {
                $addressBook = $this->getReference($addressBook);
            }
            unset($addressBook);

            $expectedUnsubscribedDates = [];
            foreach ($expectedContact['unsubscribedDate'] as $addressBookRef => $date) {
                $expectedUnsubscribedDates[$this->getReference($addressBookRef)->getId()] = $date;
            }

            $actualAddressBooks = [];
            /** @var AddressBookContact $addressBookContact */
            foreach ($actualContact->getAddressBookContacts()->toArray() as $addressBookContact) {
                if ($addressBookContact->getStatus()->getId() === Contact::STATUS_SUBSCRIBED) {
                    $actualAddressBooks[] = $addressBookContact->getAddressBook();
                } elseif (!empty($expectedUnsubscribedDates[$addressBookContact->getAddressBook()->getId()])) {
                    $this->assertEquals(
                        $expectedUnsubscribedDates[$addressBookContact->getAddressBook()->getId()],
                        $addressBookContact->getUnsubscribedDate()
                    );
                }
            }
            self::assertEqualsCanonicalizing(
                $expectedContact['subscribedAddressBooks'],
                $actualAddressBooks,
                'Subscribed Address Book Contacts is not equal',
            );
        }
    }

    public function importUnsubscribedContactsDataProvider(): array
    {
        return [
            [
                'expected'                  => [
                    [
                        'originId'               => 42,
                        'channel'                => 'oro_dotmailer.channel.third',
                        'status'                 => ApiContactStatuses::SUBSCRIBED,
                        'subscribedAddressBooks' => [
                            'oro_dotmailer.address_book.fourth',
                            'oro_dotmailer.address_book.six'
                        ],
                        'unsubscribedDate'       => [
                            'oro_dotmailer.address_book.third' => new \DateTime(
                                '2015-10-10',
                                new \DateTimeZone('UTC')
                            )
                        ]
                    ]
                ],
                'apiContactSuppressionList' => [
                    '25' => [
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
                    ],
                    '35' => []
                ]
            ]
        ];
    }
}
