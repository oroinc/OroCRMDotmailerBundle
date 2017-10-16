<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactClickList;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\DotmailerBundle\Entity\Contact;

class CampaignClickImportByAddressBookTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData',
            ]
        );
    }

    public function testImport()
    {
        /** @var Contact $contact */
        $contact = $this->getReference('oro_dotmailer.contact.nick_case.second_channel');
        $entity = new ApiCampaignContactClickList([
            [
                "contactId" => $contact->getOriginId(),
                "email" => "nick.case@example.com",
                "url" => "http://example.com/page3",
                "ipAddress" => "192.168.237.24",
                "userAgent" => "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.8.1.12)",
                "dateClicked" => "2013-01-03T20:05:00",
                "keyword" => "example"
            ]
        ]);
        $this->resource->expects($this->any())
            ->method('GetCampaignClicks')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.second');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignClickConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.second'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
