<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;
use DotMailer\Api\DataTypes\ApiContactList;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Tests\Functional\AbstractImportExportTest;

/**
 * @dbIsolation
 * @dbReindex
 */
class ExportManagerTest extends AbstractImportExportTest
{
    /**
     * @var ExportManager
     */
    protected $target;

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData',
            ]
        );
        $this->target = $this->getContainer()->get('orocrm_dotmailer.export_manager');
    }

    public function testUpdateExportResults()
    {
        $scheduledForExport = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['scheduledForExport' => true ]);
        $this->assertCount(1, $scheduledForExport);

        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;

        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with('1fb9cba7-e588-445a-8731-4796c86b1097')
            ->will($this->returnValue($apiContactImportStatus));

        $expectedEmail = 'test2@ex.com';
        $expectedId = 143;
        $entity = new ApiContactList();
        $entity[] = [
            'id'        => $expectedId,
            'email'     => $expectedEmail
        ];
        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->will($this->returnValue($entity));

        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');
        $this->target->updateExportResults($channel);

        $contacts = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Contact')
            ->findBy(
                ['channel' => $channel, 'originId' => $expectedId, 'email' => $expectedEmail  ]
            );

        $this->assertCount(1, $contacts);

        $exportEntity = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(['channel' => $channel, 'importId' => '1fb9cba7-e588-445a-8731-4796c86b1097']);

        $this->assertCount(1, $exportEntity);
        $exportEntity = reset($exportEntity);

        $this->assertEquals(AddressBookContactsExport::STATUS_FINISH, $exportEntity->getStatus()->getId());

        $scheduledForExport = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['scheduledForExport' => true ]);
        $this->assertCount(0, $scheduledForExport);
    }
}
