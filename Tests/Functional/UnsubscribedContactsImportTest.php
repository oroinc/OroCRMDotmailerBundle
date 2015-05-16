<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class UnsubscribedContactsImportExportTest extends AbstractImportExportTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData'
            ]
        );
    }

    /**
     * @dataProvider importUnsubscribedContactsDataProvider
     *
     * @param array $expected
     * @param array $apiContactSuppressionLists
     */
    public function testUnsubscribedContactsImport($expected, $apiContactSuppressionLists)
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
            ->will($this->returnCallback(function ($entityId) use ($entityMap) {
                return $entityMap[$entityId];
            }));

        $this->resource->expects($this->any())
            ->method('GetContactsUnsubscribedSinceDate')
            ->will($this->returnValue(new ApiContactSuppressionList()));
        $channel = $this->getReference('orocrm_dotmailer.channel.third');

        $processor = $this->getContainer()->get(SyncCommand::SYNC_PROCESSOR);
        $result = $processor->process($channel, UnsubscribedContactsConnector::TYPE);

        $this->assertTrue($result);

        $contactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Contact');
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

            $actualAddressBooks = [];
            /** @var AddressBookContact $addressBookContact */
            foreach ($actualContact->getAddressBookContacts()->toArray() as $addressBookContact) {
                if ($addressBookContact->getStatus()->getId() == Contact::STATUS_SUBSCRIBED) {
                    $actualAddressBooks[] = $addressBookContact->getAddressBook();
                }
            }
            $this->assertEquals($expectedContact['subscribedAddressBooks'], $actualAddressBooks);
            $this->assertEquals($expected['unsubscribedDate'], $actualContact->getUnsubscribedDate());
        }
    }

    public function importUnsubscribedContactsDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'     => 42,
                        'channel'      => 'orocrm_dotmailer.channel.third',
                        'status'       => ApiContactStatuses::SUBSCRIBED,
                        'subscribedAddressBooks' => ['orocrm_dotmailer.address_book.fourth'],
                        'unsubscribedDate' => new \DateTime('2015-10-10', new \DateTimeZone('UTC'))
                    ]
                ],
                'apiContactSuppressionList' => [
                    '25' => [
                        [
                            'suppressedContact' => [
                                'Id'         => 42,
                                'Email'      => 'test@mail.com',
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
