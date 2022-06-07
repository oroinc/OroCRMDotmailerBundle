<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ContactImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class]);
    }

    /**
     * @dataProvider importDataProvider
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testImport(array $expected, array $contactList)
    {
        $entity = new ApiContactList();
        foreach ($contactList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->willReturn($entity);

        $channel = $this->getReference('oro_dotmailer.channel.first');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $contactRepository = $this->managerRegistry->getRepository(Contact::class);
        $addressBookRepository = $this->managerRegistry->getRepository(AddressBook::class);
        $optInTypeRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cnt_opt_in_type')
        );
        $emailTypeRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cnt_email_type')
        );
        $statusRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cnt_status')
        );

        foreach ($expected as $contact) {
            $searchCriteria = [
                'channel'   => $channel,
                'originId'  => $contact['originId'],
                'email'     => $contact['email'],
            ];

            $contactEntity = $contactRepository->findOneBy($searchCriteria);
            $this->assertNotNull($contactEntity, 'Failed asserting that contact imported.');

            $this->assertEquals($contact['dataFields'], $contactEntity->getDataFields());

            if (!empty($contact['lastSubscribedDate'])) {
                $this->assertEquals($contact['lastSubscribedDate'], $contactEntity->getLastSubscribedDate());
            } else {
                $this->assertNull($contactEntity->getLastSubscribedDate());
            }

            if (empty($contact['optInType'])) {
                $this->assertNull($contactEntity->getOptInType());
            } else {
                $optInType = $optInTypeRepository->find($contact['optInType']);
                $this->assertEquals($optInType, $contactEntity->getOptInType());
            }

            if (empty($contact['emailType'])) {
                $this->assertNull($contactEntity->getEmailType());
            } else {
                $emailType = $emailTypeRepository->find($contact['emailType']);
                $this->assertEquals($emailType, $contactEntity->getEmailType());
            }

            if (empty($contact['status'])) {
                $this->assertNull($contactEntity->getStatus());
            } else {
                $status = $statusRepository->find($contact['status']);
                $this->assertEquals($status, $contactEntity->getStatus());
                /** @var AddressBookContact $addressBookContact */
                $addressBookContact = $contactEntity->getAddressBookContacts()->first();
                $this->assertEquals($status, $addressBookContact->getStatus());
            }
        }

        /**
         * Check Last imported at update for address books with marketing lists
         */
        $notLinkedAddressBookId = $this->getReference('oro_dotmailer.address_book.first')->getId();
        $notLinkedAddressBook = $addressBookRepository->find($notLinkedAddressBookId);
        $this->assertNull($notLinkedAddressBook->getLastImportedAt());

        $linkedAddressBookId = $this->getReference('oro_dotmailer.address_book.second')->getId();
        $linkedAddressBook = $addressBookRepository->find($linkedAddressBookId);

        $expectedLastImportedAt = $this->getContainer()
            ->get('oro_dotmailer.connector.contact')
            ->getLastSyncDate();
        $this->assertNotNull($expectedLastImportedAt);
        $this->assertEquals(
            $linkedAddressBook->getLastImportedAt(),
            $expectedLastImportedAt
        );
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'        => [
                    [
                        'originId' => 11,
                        'email'    => 'test11@test.com',
                        'status'   => 'SoftBounced',
                        'dataFields' => []
                    ],
                    [
                        'originId'  => 67,
                        'email'     => 'test4@test.com',
                        'optInType' => 'Single',
                        'emailType' => 'PlainText',
                        'status'    => 'Subscribed',
                        'dataFields' => [
                            'FIRSTNAME' => 'Alex',
                            'LASTNAME'  => 'Test',
                            'FULLNAME'  => 'fullname',
                            'GENDER'    => 'male',
                            'POSTCODE'  => 'postcode',
                            'LASTSUBSCRIBED' => '2015-01-01T00:00:00z',
                        ],
                        'lastSubscribedDate' => new \DateTime('2015-01-01', new \DateTimeZone('UTC'))
                    ],
                    [
                        'originId'           => 75,
                        'email'              => 'test43@test.com',
                        'optInType'          => 'VerifiedDouble',
                        'emailType'          => 'Html',
                        'status'             => 'Subscribed',
                        'dataFields' => []
                    ],
                ],
                'contactList'     => [
                    [
                        'id'     => 11,
                        'email'  => 'test11@test.com',
                        'status' => 'SoftBounced',
                    ],
                    [
                        'id'         => 67,
                        'email'      => 'test4@test.com',
                        'optInType'  => 'Single',
                        'emailType'  => 'PlainText',
                        'status'     => 'Subscribed',
                        'datafields' => [
                            [
                                'key'   => 'FIRSTNAME',
                                'value' => ['Alex']
                            ],
                            [
                                'key'   => 'LASTNAME',
                                'value' => ['Test']
                            ],
                            [
                                'key'   => 'FULLNAME',
                                'value' => ['fullname']
                            ],
                            [
                                'key'   => 'POSTCODE',
                                'value' => ['postcode']
                            ],
                            [
                                'key'   => 'GENDER',
                                'value' => ['male']
                            ],
                            [
                                'key'   => 'LASTSUBSCRIBED',
                                'value' => ['2015-01-01T00:00:00z']
                            ],
                        ]
                    ],
                    [
                        'id'        => 75,
                        'email'     => 'test43@test.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Subscribed',
                    ],
                ]
            ]
        ];
    }
}
