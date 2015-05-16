<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class UnsubscribedFromAccountContactsImportExportTest extends AbstractImportExportTest
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
     * @dataProvider importUnsubscribedFromAccountContactsDataProvider
     *
     * @param array $expected
     * @param array $apiContactSuppressionList
     */
    public function testUnsubscribedFromAccountContactsImport($expected, $apiContactSuppressionList)
    {
        $entity = new ApiContactSuppressionList();
        foreach ($apiContactSuppressionList as $listItem) {
            $entity[] = $listItem;
        }
        $this->resource->expects($this->any())
            ->method('GetContactsUnsubscribedSinceDate')
            ->will($this->returnValue($entity));
        $this->resource->expects($this->any())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
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

            $actualAddressBooks = $actualContact->getAddressBookContacts()
                ->toArray();
            $this->assertCount(0, $actualAddressBooks);
            $this->assertEquals($expectedContact['unsubscribedDate'], $actualContact->getUnsubscribedDate());
        }
    }

    public function importUnsubscribedFromAccountContactsDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'     => 42,
                        'channel'      => 'orocrm_dotmailer.channel.third',
                        'status'       => ApiContactStatuses::UNSUBSCRIBED,
                        'addressBooks' => ['orocrm_dotmailer.address_book.fourth'],
                        'unsubscribedDate' => new \DateTime('2015-10-10', new \DateTimeZone('UTC'))
                    ]
                ],
                'apiContactSuppressionList' => [
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
                ]
            ]
        ];
    }
}
