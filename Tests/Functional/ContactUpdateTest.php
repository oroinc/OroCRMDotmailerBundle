<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ContactUpdateTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class, LoadStatusData::class]);
    }

    /**
     * @dataProvider importUpdatedDataProvider
     */
    public function testImportUpdate(array $expected, array $contactList)
    {
        $this->preparePreconditions();

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.fourth');

        $entity = new ApiContactList();
        foreach ($contactList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->once())
            ->method('GetAddressBookContactsModifiedSinceDate')
            ->with(
                $expectedAddressBook->getOriginId(),
                $expectedAddressBook->getLastImportedAt()->format(\DateTime::ISO8601),
                true,
                ContactIterator::DEFAULT_BATCH_SIZE,
                0
            )
            ->willReturn($entity);

        $channel = $this->getReference('oro_dotmailer.channel.third');

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
            $this->assertNotNull($contactEntity, 'Failed asserting that contact updated.'. $log);

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
                $this->assertEquals($contact['optInType'], $optInType->getName());
            }

            if (empty($contact['emailType'])) {
                $this->assertNull($contactEntity->getEmailType());
            } else {
                $emailType = $emailTypeRepository->find($contact['emailType']);
                $this->assertEquals($contact['emailType'], $emailType->getName());
            }

            if (empty($contact['status'])) {
                $this->assertNull($contactEntity->getOptInType());
            } else {
                $status = $statusRepository->find($contact['status']);
                $this->assertEquals($contact['status'], $status->getName());
            }
        }
    }

    public function importUpdatedDataProvider(): array
    {
        return [
            [
                'expected' => [
                    [
                        'originId'  => 142,
                        'email'     => 'test1@example.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Unsubscribed',
                        'dataFields' => []
                    ],
                    [
                        'originId'  => 143,
                        'email'     => 'test2@ex.com',
                        'optInType' => 'Double',
                        'emailType' => 'Html',
                        'status'    => 'Suppressed',
                        'dataFields' => [
                            'FIRSTNAME' => 'Test2',
                            'LASTNAME'  => 'Test',
                            'FULLNAME'  => 'fullname',
                            'GENDER'    => 'male',
                            'POSTCODE'  => 'postcode',
                            'LASTSUBSCRIBED' => '2015-01-01T00:00:00z',
                        ],
                        'lastSubscribedDate' => new \DateTime('2015-01-01', new \DateTimeZone('UTC'))
                    ],
                ],
                'contactList' => [
                    [
                        'id'        => 142, //oro_dotmailer.contact.update_1
                        'email'     => 'test1@example.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Unsubscribed',
                    ],
                    [
                        'id'        => 143, //oro_dotmailer.contact.update_2
                        'email'     => 'test2@ex.com',
                        'optInType' => 'Double',
                        'emailType' => 'Html',
                        'status'    => 'Suppressed',
                        'datafields' => [
                            [
                                'key'   => 'FIRSTNAME',
                                'value' => ['Test2']
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
                ]
            ]
        ];
    }

    private function preparePreconditions(): void
    {
        /** @var AddressBook $addressBook */
        $addressBook = $this->getReference('oro_dotmailer.address_book.fourth');
        $addressBook->setLastImportedAt(new \DateTime());

        $this->managerRegistry->getManager()->flush();
    }
}
