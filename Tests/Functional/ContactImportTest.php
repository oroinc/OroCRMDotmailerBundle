<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ContactImportExportTest extends AbstractImportExportTest
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
        $processor = $this->getContainer()->get(SyncCommand::SYNC_PROCESSOR);
        $result = $processor->process($channel, ContactConnector::TYPE);

        $this->assertTrue($result, 'Failed asserting that import job ran successfully.');

        $contactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Contact');
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

            if (!empty($contact['firstName'])) {
                $searchCriteria['firstName'] = $contact['firstName'];
            }

            if (!empty($contact['lastName'])) {
                $searchCriteria['lastName'] = $contact['lastName'];
            }

            if (!empty($contact['fullName'])) {
                $searchCriteria['fullName'] = $contact['fullName'];
            }

            if (!empty($contact['gender'])) {
                $searchCriteria['gender'] = $contact['gender'];
            }

            if (!empty($contact['postcode'])) {
                $searchCriteria['postcode'] = $contact['postcode'];
            }

            if (!empty($contact['lastSubscribedDate'])) {
                $searchCriteria['lastSubscribedDate'] = $contact['lastSubscribedDate'];
            }

            $contactEntity = $contactRepository->findOneBy($searchCriteria);
            $this->assertNotNull($contactEntity, 'Failed asserting that contact imported.');

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
                    ],
                    [
                        'originId'  => 67,
                        'email'     => 'test4@test.com',
                        'optInType' => 'Single',
                        'emailType' => 'PlainText',
                        'status'    => 'Subscribed',
                        'lastName'  => 'Test',
                        'gender'    => 'male',
                        'lastSubscribedDate' => new \DateTime('2015-01-01', new \DateTimeZone('UTC'))
                    ],
                    [
                        'originId'           => 75,
                        'email'              => 'test43@test.com',
                        'optInType'          => 'VerifiedDouble',
                        'emailType'          => 'Html',
                        'status'             => 'Subscribed'
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
                                'value' => 'null'
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
