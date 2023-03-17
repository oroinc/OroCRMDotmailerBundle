<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressBookContactsExportRepositoryTest extends WebTestCase
{
    private AddressBookContactsExportRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadAddressBookContactsExportData::class]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository(AddressBookContactsExport::class);
    }

    public function testUpdateAddressBookContactsExportAttemptsCount()
    {
        /** @var AddressBookContactsExport $export */
        $export = $this->getReference('oro_dotmailer.address_book_contacts_export.first');

        $this->assertEmpty($export->getSyncAttempts());
        $this->repository->updateAddressBookContactsExportAttemptsCount($export, 10);

        $em = static::getContainer()->get('doctrine')->getManagerForClass(AddressBookContactsExport::class);
        $em->refresh($export);

        $this->assertEquals(10, $export->getSyncAttempts());
    }

    public function testUpdateAddressBookContactsStatusToUnknown()
    {
        /** @var AddressBookContactsExport $export */
        $export = $this->getReference('oro_dotmailer.address_book_contacts_export.first');

        $this->assertEquals(AddressBookContactsExport::STATUS_NOT_FINISHED, $export->getStatus()->getId());
        $status = $this->repository->getStatus(AddressBookContactsExport::STATUS_UNKNOWN);

        $this->repository->updateAddressBookContactsStatus($export, $status);

        $em = static::getContainer()->get('doctrine')->getManagerForClass(AddressBookContactsExport::class);
        $em->refresh($export);

        $this->assertEquals(AddressBookContactsExport::STATUS_UNKNOWN, $export->getStatus()->getId());
    }
}
