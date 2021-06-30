<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class MarketingListItemIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $marketingListItemsQueryBuilderProvider = $this->createMock(MarketingListItemsQueryBuilderProvider::class);
        $context = $this->createMock(ContextInterface::class);
        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($addressBookOriginId = 42);
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];

        $marketingListItemsQueryBuilderProvider->expects($this->any())
            ->method('getAddressBook')
            ->willReturn($addressBook);

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

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['execute'])
            ->addMethods(['useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->willReturnSelf();

        $executeMap = [
            [$firstItem],
            [$secondItem],
            []
        ];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function () use (&$executeMap) {
                $result = current($executeMap);
                next($executeMap);

                return $result;
            });

        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $marketingListItemsQueryBuilderProvider->expects($this->exactly(3))
            ->method('getMarketingListItemsQB')
            ->with($addressBook)
            ->willReturn($qb);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
