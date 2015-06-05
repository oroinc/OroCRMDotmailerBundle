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
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
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

        $iterator = new MarketingListItemIterator(
            $addressBook,
            $marketingListItemsQueryBuilderProvider,
            $context
        );
        $iterator->setBatchSize(1);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['execute', 'useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->will($this->returnSelf());

        $executeMap = [
            [$firstItem],
            [$secondItem],
            []
        ];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function () use (&$executeMap) {
                        $result = current($executeMap);
                        next($executeMap);

                        return $result;
                    }
                )
            );

        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

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
