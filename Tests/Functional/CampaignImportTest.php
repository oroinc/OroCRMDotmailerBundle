<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignList;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
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
        $enumOptionRepository = $this->managerRegistry->getRepository(EnumOption::class);

        foreach ($expected as $campaign) {
            $queryBuilder = $campaignRepository->createQueryBuilder('c');
            $queryBuilder->select('c')
                ->andWhere('c.originId = :originId')
                ->andWhere('c.channel = :channel')
                ->andWhere('c.name = :name')
                ->andWhere('c.subject = :subject')
                ->andWhere('c.fromName = :fromName')
                ->andWhere('c.fromAddress = :fromAddress')
                ->andWhere("JSON_EXTRACT(c.serialized_data, 'reply_action') = :replyAction")
                ->andWhere("JSON_EXTRACT(c.serialized_data, 'status') = :status")
                ->andWhere('c.isSplitTest = :isSplitTest')
                ->setParameter('originId', $campaign['originId'])
                ->setParameter('channel', $channel)
                ->setParameter('name', $campaign['name'])
                ->setParameter('subject', $campaign['subject'])
                ->setParameter('fromName', $campaign['fromName'])
                ->setParameter('fromAddress', $campaign['fromAddress'])
                ->setParameter('replyAction', $enumOptionRepository->find(
                    ExtendHelper::buildEnumOptionId('dm_cmp_reply_action', $campaign['reply_action'])
                )->getId())
                ->setParameter('isSplitTest', $campaign['isSplitTest'])
                ->setParameter('status', $enumOptionRepository->find(
                    ExtendHelper::buildEnumOptionId('dm_cmp_status', $campaign['status'])
                )->getId());

            $campaignEntities = $queryBuilder->getQuery()->getResult();

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
