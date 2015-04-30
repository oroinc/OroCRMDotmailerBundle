<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;

class AddressBookIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $iterator = new AddressBookIterator($resource);
        $iterator->setBatchSize(1);
        $items = new ApiAddressBookList();
        $expectedAddressBook = new ApiAddressBook();
        $expectedAddressBook->id = 2;
        $items[] = $expectedAddressBook;
        $resource->expects($this->exactly(2))
            ->method('GetAddressBooks')
            ->will($this->returnValueMap(
                [
                    [1, 0, $items],
                    [1, 1, new ApiAddressBookList()],
                ]
            ));
        foreach ($iterator as $item) {
            $this->assertEquals($expectedAddressBook->toArray(), $item);
        }
    }
}
