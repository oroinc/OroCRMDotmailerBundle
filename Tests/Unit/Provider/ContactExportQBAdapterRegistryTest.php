<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterRegistry;

class ContactExportQBAdapterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactExportQBAdapterRegistry
     */
    protected $target;

    protected function setUp()
    {
        $this->target = new ContactExportQBAdapterRegistry();
    }

    /**
     * @expectedException \Oro\Bundle\DotmailerBundle\Exception\RuntimeException
     * @expectedExceptionMessage Incorrect adapter format.
     */
    public function testSetAdaptersValidateAdaptersFormat()
    {
        $this->target->setAdapters([['test' => new \StdClass()]]);
    }

    /**
     * @expectedException \Oro\Bundle\DotmailerBundle\Exception\RuntimeException
     * @expectedExceptionMessage Instance of Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface
     *                           required. Instance of %s given.
     */
    public function testSetAdaptersValidateAdaptersImplementCorrectInterface()
    {
        $this->target->setAdapters(
            [
                [
                    ContactExportQBAdapterRegistry::ADAPTER_PRIORITY_KEY => 100,
                    ContactExportQBAdapterRegistry::ADAPTER_SERVICE_KEY  => new \StdClass()
                ]
            ]
        );
    }

    public function testSetAdapters()
    {
        $adapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
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
        $adapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
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

    /**
     * @expectedException \Oro\Bundle\DotmailerBundle\Exception\RuntimeException
     * @expectedExceptionMessage Provider for Address Book '21' not exist
     */
    public function testGetAdapterByAddressBookThrowAnExceptionIfHasNoAdapters()
    {
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(21));
        $this->target->getAdapterByAddressBook($addressBook);
    }

    /**
     * @expectedException \Oro\Bundle\DotmailerBundle\Exception\RuntimeException
     * @expectedExceptionMessage Provider for Address Book '21' not exist
     */
    public function testGetAdapterByAddressBookThrowAnExceptionIfHasApplicableAdapters()
    {
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(21));
        $firstAdapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(false));

        $secondAdapter = clone $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(false));

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
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $firstAdapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(true));

        $secondAdapter = clone $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(true));

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
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $firstAdapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $firstAdapter->expects($this->any())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(true));

        $secondAdapter = $this->createMock('Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface');
        $secondAdapter->expects($this->once())
            ->method('isApplicable')
            ->with($addressBook)
            ->will($this->returnValue(false));

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
