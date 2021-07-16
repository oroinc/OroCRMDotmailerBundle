<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactResubscription;
use DotMailer\Api\DataTypes\ApiDataField;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiResubscribeResult;
use DotMailer\Api\DataTypes\Int32List;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DotmailerTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DotmailerTransport
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $encoder;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(
            'Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory'
        );

        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->encoder = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new DotmailerTransport(
            $this->factory,
            $this->encoder
        );
        $this->target->setLogger($this->logger);
    }

    public function testInit()
    {
        $username = 'John';
        $password = '42';
        $passwordEncoded = md5($password);
        $clientId = uniqid();
        $clientKey = uniqid();
        $clientKeyEncoded = md5($clientKey);
        $transport = $this->createMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->createMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $settingsBag->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, $username],
                    ['password', null, $passwordEncoded],
                    ['clientId', null, $clientId],
                    ['clientKey', null, $clientKeyEncoded],
                ]
            ));
        $transport->expects($this->once())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));

        $this->encoder->expects($this->once())
            ->method('decryptData')
            ->with($passwordEncoded)
            ->will($this->returnValue($password));

        $this->factory->expects($this->once())
            ->method('createResources')
            ->with($username, $password, $this->logger);

        $this->target->init($transport);
    }

    public function testInitThrowAnExceptionIfUsernameOptionsEmpty()
    {
        $this->expectException(\Oro\Bundle\DotmailerBundle\Exception\RequiredOptionException::class);
        $this->expectExceptionMessage('Option "password" is required');

        $transport = $this->createMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->createMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));
        $settingsBag->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, 'any not empty username'],
                    ['password', null, null],
                ]
            ));

        $this->target->init($transport);
    }

    public function testInitThrowAnExceptionIfPasswordOptionsEmpty()
    {
        $this->expectException(\Oro\Bundle\DotmailerBundle\Exception\RequiredOptionException::class);
        $this->expectExceptionMessage('Option "username" is required');

        $transport = $this->createMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->createMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));

        $this->target->init($transport);
    }

    public function testGetUnsubscribedFromAccountsContactsWithoutSyncDate()
    {
        $iterator = $this->target->getUnsubscribedFromAccountsContacts();
        $this->assertInstanceOf('\EmptyIterator', $iterator);
    }

    public function testGetUnsubscribedFromAccountsContacts()
    {
        $resource = $this->initTransportStub();

        $expectedDate = new \DateTime();
        $iterator = $this->target->getUnsubscribedFromAccountsContacts($expectedDate);
        /**
         * Test iterator initialized with correct address book origin id and last sync date
         */
        $contactsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetContactsSuppressedSinceDate')
            ->with($expectedDate->format(\DateTime::ISO8601))
            ->will($this->returnValue($contactsList));
        $iterator->rewind();
    }

    /**
     * GetContactsUnsubscribedSinceDate
     */
    public function testGetUnsubscribedContactsWithoutSyncDate()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $expectedAddressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $expectedAddressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($expectedAddressBookOriginId);

        $expectedDate = date_create_from_format(
            'Y',
            DotmailerTransport::DEFAULT_START_SYNC_DATE,
            new \DateTimeZone('UTC')
        );
        $iterator = $this->target->getUnsubscribedContacts(
            [$expectedAddressBook]
        );
        $this->assertInstanceOf(
            AppendIterator::class,
            $iterator
        );

        /**
         * Test iterator initialized with correct address book origin id and last sync date
         */
        $contactsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with(
                $expectedAddressBookOriginId,
                $expectedDate->format(\DateTime::ISO8601)
            )
            ->will($this->returnValue($contactsList));
        $iterator->rewind();
    }

    public function testGetUnsubscribedContacts()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $expectedDate = new \DateTime();
        $expectedAddressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $expectedAddressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($expectedAddressBookOriginId);
        $expectedAddressBook->expects($this->any())
            ->method('getLastImportedAt')
            ->willReturn($expectedDate);

        $iterator = $this->target->getUnsubscribedContacts(
            [$expectedAddressBook]
        );
        $this->assertInstanceOf(
            AppendIterator::class,
            $iterator
        );

        /**
         * Test iterator initialized with correct address book origin id and last sync date
         */
        $contactsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with(
                $expectedAddressBookOriginId,
                $expectedDate->format(\DateTime::ISO8601)
            )
            ->will($this->returnValue($contactsList));
        $iterator->rewind();
    }

    public function testGetCampaignsWithoutAddressBooks()
    {
        $iterator = $this->target->getCampaigns([]);
        $this->assertInstanceOf('\Iterator', $iterator);

        $this->assertEquals(0, iterator_count($iterator));
    }

    public function testGetCampaignsWithAddressBooks()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $iterator = $this->target->getCampaigns([0 => ['originId' => $expectedAddressBookOriginId]]);
        $this->assertInstanceOf(
            AppendIterator::class,
            $iterator
        );

        /**
         * Test iterator initialized with correct address book origin id
         */
        $campaignsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $campaignsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetAddressBookCampaigns')
            ->with($expectedAddressBookOriginId)
            ->will($this->returnValue($campaignsList));
        $iterator->rewind();
    }

    public function testGetContactsWithoutSyncDate()
    {
        $resource = $this->initTransportStub();
        $expectedAddressBookOriginId = 42;

        $contactsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));

        $resource->expects($this->once())
            ->method('GetAddressBookContacts')
            ->with($expectedAddressBookOriginId, true, 1000, 0)
            ->will($this->returnValue($contactsList));

        $expectedAddressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $expectedAddressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($expectedAddressBookOriginId);
        $expectedAddressBook->expects($this->any())
            ->method('getLastImportedAt')
            ->willReturn(null);

        $iterator = $this->target->getAddressBookContacts([$expectedAddressBook]);
        $iterator->rewind();
    }

    public function testGetContacts()
    {
        $resource = $this->initTransportStub();
        $expectedAddressBookOriginId = 42;

        $dateSince = new \DateTime();

        $contactsList = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([['id' => 1, 'email' => 'test@test.com']]));

        $resource->expects($this->once())
            ->method('GetAddressBookContactsModifiedSinceDate')
            ->with($expectedAddressBookOriginId, $dateSince->format(\DateTime::ISO8601), true, 1000, 0)
            ->will($this->returnValue($contactsList));

        $expectedAddressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $expectedAddressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($expectedAddressBookOriginId);
        $expectedAddressBook->expects($this->any())
            ->method('getLastImportedAt')
            ->willReturn($dateSince);

        $iterator = $this->target->getAddressBookContacts([$expectedAddressBook]);
        $iterator->rewind();
    }

    public function testResubscribeAddressBookContact()
    {
        $resource = $this->initTransportStub();
        $contact = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\Contact');
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $expected = new ApiResubscribeResult();
        $addressBookId = 42;
        $addressBook->expects($this->once())
            ->method('getOriginId')
            ->will($this->returnValue($addressBookId));
        $contact->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue($email = 'test@mail.com'));

        $resource->expects($this->once())
            ->method('PostAddressBookContactsResubscribe')
            ->with($addressBookId, $this->callback(function (ApiContactResubscription $actual) use ($email) {
                $this->assertEquals($email, $actual->unsubscribedContact->email);

                return true;
            }))
            ->will($this->returnValue($expected));

        $actual = $this->target->resubscribeAddressBookContact($contact, $addressBook);
        $this->assertEquals($expected, $actual);
    }

    public function testRemoveContactsFromAddressBook()
    {
        $addressBookId = 42;
        $removingItemsOriginIds = [21, 567];

        $resource = $this->initTransportStub();
        $resource->expects($this->once())
            ->method('PostAddressBookContactsDelete')
            ->with($addressBookId, $this->callback(function (Int32List $list) use ($removingItemsOriginIds) {
                $actual = $list->toArray();
                $this->assertEquals($removingItemsOriginIds, $actual);

                return true;
            }));

        $this->target->removeContactsFromAddressBook($removingItemsOriginIds, $addressBookId);
    }

    public function testExportAddressBookContacts()
    {
        $resource = $this->initTransportStub();
        $addressBookId = 42;

        $testCsv = "Email\ntest@mail.com";

        $import = new ApiContactImport();
        $resource->expects($this->once())
            ->method('PostAddressBookContactsImport')
            ->with($addressBookId, $this->callback(function (ApiFileMedia $apiFileMedia) use ($testCsv) {
                $this->assertEquals(base64_encode($testCsv), $apiFileMedia->data);

                return true;
            }))
            ->will($this->returnValue($import));

        $actual = $this->target->exportAddressBookContacts($testCsv, $addressBookId);
        $this->assertSame($import, $actual);
    }

    public function testGetImportStatus()
    {
        $resource = $this->initTransportStub();
        $expected = new ApiContactImport();
        $resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with($importId = '2d2cac85-e292-4f35-988c-ddb5ba40dda0')
            ->will($this->returnValue($expected));

        $actual = $this->target->getImportStatus($importId);
        $this->assertSame($expected, $actual);
    }

    public function testGetDataFields()
    {
        $resource = $this->initTransportStub();
        $actual = $this->target->getDataFields();
        $this->assertInstanceOf('Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\DataFieldIterator', $actual);
    }

    public function testRemoveDataField()
    {
        $fieldName = 'test_name';
        $resource = $this->initTransportStub();
        $result = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $result->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('DeleteDataField')
            ->with($fieldName)
            ->will($this->returnValue($result));

        $this->assertSame([], $this->target->removeDataField($fieldName));
    }

    public function testCreateDataField()
    {
        $data = new ApiDataField();
        $resource = $this->initTransportStub();
        $resource->expects($this->once())
            ->method('PostDataFields')
            ->with($data);

        $this->target->createDataField($data);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \Oro\Bundle\DotmailerBundle\Exception\RequiredOptionException
     */
    protected function initTransportStub()
    {
        $username = 'John';
        $password = '42';
        $passwordEncoded = md5($password);
        $clientId = uniqid();
        $clientKey = uniqid();
        $clientKeyEncoded = md5($clientKey);
        $transport = $this->createMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->createMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $settingsBag->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, $username],
                    ['password', null, $passwordEncoded],
                    ['clientId', null, $clientId],
                    ['clientKey', null,$clientKeyEncoded],
                ]
            ));

        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));
        $resource = $this->createMock('DotMailer\Api\Resources\IResources');

        $this->encoder->expects($this->once())
            ->method('decryptData')
            ->with($passwordEncoded)
            ->will($this->returnValue($password));

        $this->factory->expects($this->any())
            ->method('createResources')
            ->with($username, $password, $this->logger)
            ->will($this->returnValue($resource));

        $this->target->init($transport);

        return $resource;
    }
}
