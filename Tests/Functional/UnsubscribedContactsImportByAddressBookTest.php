<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactEmailTypes;
use DotMailer\Api\DataTypes\ApiContactStatuses;
use DotMailer\Api\DataTypes\ApiContactSuppressionList;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;

class UnsubscribedContactsImportByAddressBookTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData'
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiContactSuppressionList([
            [
                'suppressedContact' => [
                    'Id'         => 42,
                    'Email'      => 'second@mail.com',
                    'EmailType'  => ApiContactEmailTypes::PLAIN_TEXT,
                    'DataFields' => [],
                    'Status'     => ApiContactStatuses::SUBSCRIBED
                ],
                'dateRemoved'       => '2015-10-10T00:00:00z',
                'reason'            => ApiContactStatuses::UNSUBSCRIBED
            ]
        ]);
        $this->resource->expects($this->any())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->will($this->returnValue($entity));
        $this->resource->expects($this->any())
            ->method('GetContactsSuppressedSinceDate')
            ->will($this->returnValue(new ApiContactSuppressionList()));
        $channel = $this->getReference('oro_dotmailer.channel.third');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            UnsubscribedContactConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.third'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
