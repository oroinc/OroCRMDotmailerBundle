<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactResubscription;
use DotMailer\Api\DataTypes\ApiDataField;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiResubscribeResult;
use DotMailer\Api\DataTypes\Int32List;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AppendIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\DataFieldIterator;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DotmailerTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var DotmailerResourcesFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encoder;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DotmailerTransport */
    private $target;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(DotmailerResourcesFactory::class);
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->target = new DotmailerTransport($this->factory, $this->encoder);
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
        $transport = $this->createMock(Transport::class);
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['username', null, $username],
                ['password', null, $passwordEncoded],
                ['clientId', null, $clientId],
                ['clientKey', null, $clientKeyEncoded],
            ]);
        $transport->expects($this->once())
            ->method('getSettingsBag')
            ->willReturn($settingsBag);

        $this->encoder->expects($this->once())
            ->method('decryptData')
            ->with($passwordEncoded)
            ->willReturn($password);

        $this->factory->expects($this->once())
            ->method('createResources')
            ->with($username, $password, $this->logger);

        $this->target->init($transport);
    }

    public function testInitThrowAnExceptionIfUsernameOptionsEmpty()
    {
        $this->expectException(RequiredOptionException::class);
        $this->expectExceptionMessage('Option "password" is required');

        $transport = $this->createMock(Transport::class);
        $settingsBag = $this->createMock(ParameterBag::class);
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn($settingsBag);
        $settingsBag->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['username', null, 'any not empty username'],
                ['password', null, null],
            ]);

        $this->target->init($transport);
    }

    public function testInitThrowAnExceptionIfPasswordOptionsEmpty()
    {
        $this->expectException(RequiredOptionException::class);
        $this->expectExceptionMessage('Option "username" is required');

        $transport = $this->createMock(Transport::class);
        $settingsBag = $this->createMock(ParameterBag::class);
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn($settingsBag);

        $this->target->init($transport);
    }

    public function testGetUnsubscribedFromAccountsContactsWithoutSyncDate()
    {
        $iterator = $this->target->getUnsubscribedFromAccountsContacts();
        $this->assertInstanceOf(\EmptyIterator::class, $iterator);
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
            ->willReturn([]);
        $resource->expects($this->once())
            ->method('GetContactsSuppressedSinceDate')
            ->with($expectedDate->format(\DateTime::ISO8601))
            ->willReturn($contactsList);
        $iterator->rewind();
    }

    /**
     * GetContactsUnsubscribedSinceDate
     */
    public function testGetUnsubscribedContactsWithoutSyncDate()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $expectedAddressBook = $this->createMock(AddressBook::class);
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
            ->willReturn([]);
        $resource->expects($this->once())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with(
                $expectedAddressBookOriginId,
                $expectedDate->format(\DateTime::ISO8601)
            )
            ->willReturn($contactsList);
        $iterator->rewind();
    }

    public function testGetUnsubscribedContacts()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $expectedDate = new \DateTime();
        $expectedAddressBook = $this->createMock(AddressBook::class);
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
            ->willReturn([]);
        $resource->expects($this->once())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with(
                $expectedAddressBookOriginId,
                $expectedDate->format(\DateTime::ISO8601)
            )
            ->willReturn($contactsList);
        $iterator->rewind();
    }

    public function testGetCampaignsWithoutAddressBooks()
    {
        $iterator = $this->target->getCampaigns([]);
        $this->assertInstanceOf(\Iterator::class, $iterator);

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
            ->willReturn([]);
        $resource->expects($this->once())
            ->method('GetAddressBookCampaigns')
            ->with($expectedAddressBookOriginId)
            ->willReturn($campaignsList);
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
            ->willReturn([]);

        $resource->expects($this->once())
            ->method('GetAddressBookContacts')
            ->with($expectedAddressBookOriginId, true, 1000, 0)
            ->willReturn($contactsList);

        $expectedAddressBook = $this->createMock(AddressBook::class);
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
            ->willReturn([['id' => 1, 'email' => 'test@test.com']]);

        $resource->expects($this->once())
            ->method('GetAddressBookContactsModifiedSinceDate')
            ->with($expectedAddressBookOriginId, $dateSince->format(\DateTime::ISO8601), true, 1000, 0)
            ->willReturn($contactsList);

        $expectedAddressBook = $this->createMock(AddressBook::class);
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
        $contact = $this->createMock(Contact::class);
        $addressBook = $this->createMock(AddressBook::class);
        $expected = new ApiResubscribeResult();
        $addressBookId = 42;
        $addressBook->expects($this->once())
            ->method('getOriginId')
            ->willReturn($addressBookId);
        $contact->expects($this->once())
            ->method('getEmail')
            ->willReturn($email = 'test@mail.com');

        $resource->expects($this->once())
            ->method('PostAddressBookContactsResubscribe')
            ->with($addressBookId, $this->callback(function (ApiContactResubscription $actual) use ($email) {
                $this->assertEquals($email, $actual->unsubscribedContact->email);

                return true;
            }))
            ->willReturn($expected);

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
            ->willReturn($import);

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
            ->willReturn($expected);

        $actual = $this->target->getImportStatus($importId);
        $this->assertSame($expected, $actual);
    }

    public function testGetDataFields()
    {
        $this->initTransportStub();
        $actual = $this->target->getDataFields();
        $this->assertInstanceOf(DataFieldIterator::class, $actual);
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
            ->willReturn([]);
        $resource->expects($this->once())
            ->method('DeleteDataField')
            ->with($fieldName)
            ->willReturn($result);

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

    private function initTransportStub(): IResources|\PHPUnit\Framework\MockObject\MockObject
    {
        $username = 'John';
        $password = '42';
        $passwordEncoded = md5($password);
        $clientId = uniqid();
        $clientKey = uniqid();
        $clientKeyEncoded = md5($clientKey);
        $transport = $this->createMock(Transport::class);
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['username', null, $username],
                ['password', null, $passwordEncoded],
                ['clientId', null, $clientId],
                ['clientKey', null, $clientKeyEncoded],
            ]);

        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn($settingsBag);
        $resource = $this->createMock(IResources::class);

        $this->encoder->expects($this->once())
            ->method('decryptData')
            ->with($passwordEncoded)
            ->willReturn($password);

        $this->factory->expects($this->any())
            ->method('createResources')
            ->with($username, $password, $this->logger)
            ->willReturn($resource);

        $this->target->init($transport);

        return $resource;
    }
}
