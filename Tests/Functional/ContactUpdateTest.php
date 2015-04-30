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
class ContactUpdateTest extends AbstractImportTest
{
    /**
     * {@inheritdoc}
     */
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
     * @dataProvider importUpdatedDataProvider
     *
     * @param array $expected
     * @param array $contactList
     */
    public function testImportUpdate($expected, $contactList)
    {
        $entity = new ApiContactList();
        foreach ($contactList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetContactsModifiedSinceDate')
            ->will($this->returnValue($entity));

        $channel = $this->getReference('orocrm_dotmailer.channel.second');
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
            $this->assertNotNull($contactEntity, 'Failed asserting that contact updated.');

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


    /**
     * @return array
     */
    public function importUpdatedDataProvider()
    {
        return [
            [
                'expected'        => [
                    [
                        'originId'  => 142,
                        'email'     => 'test1@example.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Unsubscribed',
                    ],
                    [
                        'originId'  => 143,
                        'email'     => 'test2@example.com',
                        'optInType' => 'Double',
                        'emailType' => 'Html',
                        'status'    => 'Suppressed',
                    ],
                ],
                'addressBookList' => [
                    [
                        'id'        => 142,
                        'email'     => 'test1@example.com',
                        'optInType' => 'VerifiedDouble',
                        'emailType' => 'Html',
                        'status'    => 'Unsubscribed',
                    ],
                    [
                        'id'        => 143,
                        'email'     => 'test2@example.com',
                        'optInType' => 'Double',
                        'emailType' => 'Html',
                        'status'    => 'Suppressed',
                    ],
                ]
            ]
        ];
    }
}