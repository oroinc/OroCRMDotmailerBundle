<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

class ContactImportByAddressBookTest extends AbstractImportExportTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiContactList([
            [
                'id'     => 11,
                'email'  => 'test11@test.com',
                'status' => 'SoftBounced',
            ],
        ]);
        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.first');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ContactConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.second'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
