<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactOptInTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;

class UnsubscribedContactsImportTest extends AbstractImportTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
     *
     * @param array $expected
     * @param array $apiContactSuppressionList
     */
    public function testImport($expected, $apiContactSuppressionList)
    {
        $entity = new ApiContactSuppressionList();
        foreach ($apiContactSuppressionList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('orocrm_dotmailer.channel.first');

        $processor = $this->getContainer()->get(SyncCommand::SYNC_PROCESSOR);
        $result = $processor->process($channel, UnsubscribedContactsConnector::TYPE);

        $this->assertTrue($result);

        $contactRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Contact');
        $statusRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_ab_visibility')
        );

        foreach ($expected as $expectedContact) {
            $searchCriteria = [
                'originId' => $expectedContact['originId'],
                'status' => $statusRepository->find($expectedContact['status'])
            ];

            $actualContacts = $contactRepository->findBy($searchCriteria);
            $this->assertCount(1, $actualContacts);

            $this->assertEquals($this->getReference($expectedContact['channel']), $actualContacts[0]->getChannel());
        }
    }

    public function importDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'     => 42,
                        'channel'      => 'orocrm_dotmailer.channel.first',
                        'status'       => Contact::STATUS_SUPPRESSED
                    ]
                ],
                'apiContactSuppressionList' => [
                    [
                        'suppressedContact'         => [
                            'Id' => 42,
                            'Email' => 'test@mail.com',
                            'OptInType' => ApiContactOptInTypes::SINGLE,
                            'EmailType' => ApiContactEmailTypes::PLAIN_TEXT,
                            'DataFields' => [],
                            'Status' => ApiContactStatuses::SUBSCRIBED
                        ],
                        'dateRemoved' => '2015-10-10',
                        'reason' => ApiContactStatuses::SUPPRESSED
                    ]
                ]
            ]
        ];
    }
}
