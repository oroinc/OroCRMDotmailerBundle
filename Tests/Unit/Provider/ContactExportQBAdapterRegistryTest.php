<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface;
use Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterRegistry;

class ContactExportQBAdapterRegistryTest extends \PHPUnit\Framework\TestCase
{
    private ContactExportQBAdapterRegistry $target;

    protected function setUp(): void
    {
        $this->target = new ContactExportQBAdapterRegistry();
    }

    public function testSetAdaptersValidateAdaptersFormat()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Incorrect adapter format.');

        $this->target->setAdapters([['test' => new \stdClass()]]);
    }

    public function testSetAdaptersValidateAdaptersImplementCorrectInterface()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Instance of %s required. Instance of stdClass given.',
            ContactExportQBAdapterInterface::class
        ));

        $this->target->setAdapters(
            [
                [
                    ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                    ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => new \stdClass()
                ]
            ]
        );
    }

    public function testSetAdapters()
    {
        $adapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $expectedAdapters = [
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $adapter
            ]
        ];
        $actual = $this->target
            ->setAdapters($expectedAdapters)
            ->getAdapters();

        $this->assertEquals($expectedAdapters, $actual);
    }

    public function testAddAdapter()
    {
        $adapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $expectedAdapters = [
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => $priority = 150,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $adapter
            ]
        ];

        $actual = $this->target
            ->addAdapter($adapter, $priority)
            ->getAdapters();

        $this->assertEquals($expectedAdapters, $actual);
    }

    public function testGetAdapterByAddressBookThrowAnExceptionIfHasNoAdapters()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Provider for Address Book '21' not exist");

        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->once())
            ->method('getId')
            ->willReturn(21);
        $this->target->getAdapterByAddressBook($addressBook);
    }

    public function testGetAdapterByAddressBookThrowAnExceptionIfHasApplicableAdapters()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Provider for Address Book '21' not exist");

        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->once())
            ->method('getId')
            ->willReturn(21);
        $firstAdapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(false);

        $secondAdapter = clone $this->createMock(ContactExportQBAdapterInterface::class);
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(false);

        $expectedAdapters = [
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $firstAdapter
            ],
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 200,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $secondAdapter
            ],
        ];
        $this->target
            ->setAdapters($expectedAdapters)
            ->getAdapterByAddressBook($addressBook);
    }

    public function testGetAdapterByAddressBookReturnAdapterWithMaxPriority()
    {
        $addressBook = $this->createMock(AddressBook::class);
        $firstAdapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(true);

        $secondAdapter = clone $this->createMock(ContactExportQBAdapterInterface::class);
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(true);

        $expectedAdapters = [
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $firstAdapter
            ],
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 200,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $secondAdapter
            ],
        ];
        $actual = $this->target
            ->setAdapters($expectedAdapters)
            ->getAdapterByAddressBook($addressBook);

        $this->assertSame($secondAdapter, $actual);
    }

    public function testGetAdapterByAddressBookReturnApplicableAdapter()
    {
        $addressBook = $this->createMock(AddressBook::class);
        $firstAdapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(true);

        $secondAdapter = $this->createMock(ContactExportQBAdapterInterface::class);
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->willReturn(false);

        $expectedAdapters = [
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $firstAdapter
            ],
            [
                ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 200,
                ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => $secondAdapter
            ],
        ];
        $actual = $this->target
            ->setAdapters($expectedAdapters)
            ->getAdapterByAddressBook($addressBook);

        $this->assertSame($firstAdapter, $actual);
    }
}
