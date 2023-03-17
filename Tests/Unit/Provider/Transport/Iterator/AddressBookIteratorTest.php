<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;

class AddressBookIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $resource = $this->createMock(IResources::class);
        $iterator = new AddressBookIterator($resource);
        $iterator->setBatchSize(1);
        $items = new ApiAddressBookList();
        $expectedAddressBook = new ApiAddressBook();
        $expectedAddressBook->id = 2;
        $items[] = $expectedAddressBook;
        $resource->expects($this->exactly(2))
            ->method('GetAddressBooks')
            ->willReturnMap([
                [1, 0, $items],
                [1, 1, new ApiAddressBookList()],
            ]);
        foreach ($iterator as $item) {
            $this->assertEquals($expectedAddressBook->toArray(), $item);
        }
    }
}
