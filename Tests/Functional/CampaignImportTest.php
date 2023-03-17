<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignList;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class CampaignImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAddressBookData::class]);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testImport(array $expected, array $campaignList)
    {
        $entity = new ApiCampaignList();
        foreach ($campaignList as $listItem) {
            $entity[] = $listItem;
        }

        $this->resource->expects($this->any())
            ->method('GetAddressBookCampaigns')
            ->willReturn($entity);
        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $campaignRepository = $this->managerRegistry->getRepository(Campaign::class);
        $replyActionRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cmp_reply_action')
        );
        $statusRepository = $this->managerRegistry->getRepository(
            ExtendHelper::buildEnumValueClassName('dm_cmp_status')
        );

        foreach ($expected as $campaign) {
            $searchCriteria = [
                'originId'     => $campaign['originId'],
                'channel'      => $channel,
                'name'         => $campaign['name'],
                'subject'      => $campaign['subject'],
                'fromName'     => $campaign['fromName'],
                'fromAddress'  => $campaign['fromAddress'],
                'reply_action' => $replyActionRepository->find($campaign['reply_action']),
                'isSplitTest'  => $campaign['isSplitTest'],
                'status'       => $statusRepository->find($campaign['status']),
            ];

            $campaignEntities = $campaignRepository->findBy($searchCriteria);

            $this->assertCount(1, $campaignEntities);
            /** @var Campaign $actualCampaign */
            $actualCampaign = $campaignEntities[0];

            $actualAddressBooks = $actualCampaign->getAddressBooks()->toArray();
            foreach ($campaign['addressBooks'] as &$expectedAddressBook) {
                $expectedAddressBook = $this->getReference($expectedAddressBook);
            }
            unset($expectedAddressBook);
            $this->assertEquals($campaign['addressBooks'], $actualAddressBooks);

            $emailCampaign = $actualCampaign->getEmailCampaign();
            $this->assertNotNull($emailCampaign, 'Email Campaign should be added automatically');
            $this->assertEquals($emailCampaign->getName(), $campaign['name']);

            $marketingCampaign = $emailCampaign->getCampaign();
            $this->assertNotNull($marketingCampaign, 'Marketing Campaign should be added automatically');
            $this->assertEquals($marketingCampaign->getName(), $campaign['name']);
            self::assertStringContainsString($campaign['name'], $marketingCampaign->getDescription());
            self::assertStringContainsString((string) $campaign['originId'], $marketingCampaign->getCode());
        }
    }

    public function importDataProvider(): array
    {
        return [
            [
                'expected'     => [
                    [
                        'originId'     => 15662,
                        'name'         => 'NewsLetter',
                        'subject'      => 'News Letter',
                        'fromName'     => 'CityBeach',
                        'fromAddress'  => 'Arbitbet@dotmailer-email.com',
                        'reply_action' => 'Webmail',
                        'isSplitTest'  => false,
                        'status'       => 'Sent',
                        'addressBooks' => ['oro_dotmailer.address_book.second']
                    ],
                ],
                'campaignList' => [
                    [
                        'id'               => 15662,
                        'name'             => 'NewsLetter',
                        'subject'          => 'News Letter',
                        'fromname'         => 'CityBeach',
                        'fromaddress'      => [
                            'id'    => 6141,
                            'email' => 'Arbitbet@dotmailer-email.com',
                        ],
                        'htmlcontent'      => 'null',
                        'plaintextcontent' => 'null',
                        'replyaction'      => 'Webmail',
                        'replytoaddress'   => '',
                        'issplittest'      => 'false',
                        'status'           => 'Sent'
                    ],
                ]
            ]
        ];
    }
}
