<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ContactImportTest extends AbstractImportTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData'
            ]
        );
    }

    /**
     * @dataProvider importDataProvider
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
            ->method('GetContacts')
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

            $contactEntity = $contactRepository->findOneBy($searchCriteria);
            $this->assertNotNull($contactEntity, 'Failed asserting that contact imported.');

            if (empty($expected['optInType'])) {
                $this->assertNull($contactEntity->getOptInType());
            } else {
                $optInType = $optInTypeRepository->find($contact['optInType']);
                $this->assertEquals($expected['optInType'], $optInType->getName());
            }

            if (empty($expected['emailType'])) {
                $this->assertNull($contactEntity->getEmailType());
            } else {
                $emailType = $emailTypeRepository->find($contact['emailType']);
                $this->assertEquals($expected['emailType'], $emailType->getName());
            }

            if (empty($expected['status'])) {
                $this->assertNull($contactEntity->getOptInType());
            } else {
                $status = $statusRepository->find($contact['status']);
                $this->assertEquals($expected['status'], $status->getName());
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
                        'email'    => 'test1@test.com',
                    ],
                    [
                        'originId'  => 67,
                        'email'     => 'test4@test.com',
                        'optInType' => 'Single',
                        'emailType' => 'PlainText',
                        'status'    => 'Subscribed',
                    ],
                    [
                        'originId'  => 75,
                        'email'     => 'test43@test.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Subscribed',
                    ],
                ],
                'addressBookList' => [
                    [
                        'id'    => 11,
                        'email' => 'test1@test.com',
                    ],
                    [
                        'id'        => 67,
                        'email'     => 'test4@test.com',
                        'optInType' => 'Single',
                        'emailType' => 'PlainText',
                        'status'    => 'Subscribed',
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
