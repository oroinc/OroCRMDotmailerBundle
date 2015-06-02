<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class MarketingListItemIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $marketingListItemsQueryBuilderProvider = $this->getMockBuilder(
            'OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $addressBook = $this->getMock('OroCRM\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->will($this->returnValue($addressBookOriginId = 42));
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];

        $expectedItems = [
            ['id' => 23, MarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, MarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $iterator = new MarketingListItemIterator($addressBook, $marketingListItemsQueryBuilderProvider);
        $iterator->setBatchSize(1);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $qb->expects($this->exactly(3))
            ->method('setFirstResult');
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->exactly(3))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $query->expects($this->at(0))
            ->method('execute')
            ->will($this->returnValue([$firstItem]));
        $query->expects($this->at(1))
            ->method('execute')
            ->will($this->returnValue([$secondItem]));
        $query->expects($this->at(2))
            ->method('execute')
            ->will($this->returnValue([]));

        $marketingListItemsQueryBuilderProvider->expects($this->exactly(3))
            ->method('getMarketingListItemsQB')
            ->with($addressBook)
            ->will($this->returnValue($qb));

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
