<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 */
class ContactImportTest extends AbstractImportExportTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @param array $expected
     * @param array $contactList
     */
    public function testImport($expected, $contactList)
    {
        $entity = new ApiContactList();
        foreach ($contactList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->will($this->returnValue($entity));

        $channel = $this->getReference('orocrm_dotmailer.channel.first');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $contactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Contact');
        $addressBookRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBook');
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

            $this->assertEquals($contact['firstName'], $contactEntity->getFirstName());
            $this->assertEquals($contact['lastName'], $contactEntity->getLastName());
            $this->assertEquals($contact['fullName'], $contactEntity->getFullName());
            $this->assertEquals($contact['gender'], $contactEntity->getGender());
            $this->assertEquals($contact['postcode'], $contactEntity->getPostcode());

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
        $notLinkedAddressBookId = $this->getReference('orocrm_dotmailer.address_book.first')->getId();
        $notLinkedAddressBook = $addressBookRepository->find($notLinkedAddressBookId);
        $this->assertNull($notLinkedAddressBook->getLastImportedAt());

        $linkedAddressBookId = $this->getReference('orocrm_dotmailer.address_book.second')->getId();
        $linkedAddressBook = $addressBookRepository->find($linkedAddressBookId);

        $expectedLastImportedAt = $this->getContainer()
            ->get('orocrm_dotmailer.connector.contact')
            ->getLastSyncDate();
        $this->assertNotNull($expectedLastImportedAt);
        $this->assertEquals(
            $linkedAddressBook->getLastImportedAt(),
            $expectedLastImportedAt
        );
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId' => 11,
                        'email'    => 'test11@test.com',
                        'status'   => 'SoftBounced',
                        'firstName' => null,
                        'lastName' => null,
                        'fullName' => null,
                        'gender' => null,
                        'postcode' => null,
                    ],
                    [
                        'originId'  => 67,
                        'email'     => 'test4@test.com',
                        'optInType' => 'Single',
                        'emailType' => 'PlainText',
                        'status'    => 'Subscribed',
                        'lastName'  => 'Test',
                        'firstName' => 'Alex',
                        'fullName' => null,
                        'gender'    => 'male',
                        'postcode' => null,
                        'lastSubscribedDate' => new \DateTime('2015-01-01', new \DateTimeZone('UTC'))
                    ],
                    [
                        'originId'           => 75,
                        'email'              => 'test43@test.com',
                        'optInType'          => 'VerifiedDouble',
                        'emailType'          => 'Html',
                        'status'             => 'Subscribed',
                        'firstName' => null,
                        'lastName' => null,
                        'fullName' => null,
                        'gender' => null,
                        'postcode' => null,
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
                                'value' => 'null'
                            ],
                            [
                                'key'   => 'POSTCODE',
                                'value' => 'null'
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
