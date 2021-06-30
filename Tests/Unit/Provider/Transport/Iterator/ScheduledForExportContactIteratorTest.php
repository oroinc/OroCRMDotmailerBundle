<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

class ScheduledForExportContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($addressBookOriginId = 234);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getReference')
            ->willReturn($addressBook);

        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];
        $thirdItem = ['id' => 144];
        $expectedItems = [
            ['id' => 23, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 144, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->exactly(2))
            ->method('setMaxResults')
            ->with($batchSize = 2)
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setFirstResult')
            ->willReturnSelf();
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['getArrayResult'])
            ->addMethods(['useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->exactly(2))
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->exactly(2))
            ->method('useQueryCache')
            ->willReturnSelf();

        $getArrayResultMap = [
            [$firstItem, $secondItem],
            [$thirdItem]
        ];
        $query->expects($this->exactly(2))
            ->method('getArrayResult')
            ->willReturnCallback(function () use (&$getArrayResultMap) {
                $result = current($getArrayResultMap);
                next($getArrayResultMap);

                return $result;
            });
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->exactly(2))
            ->method('getScheduledForExportByChannelQB')
            ->with($addressBook)
            ->willReturn($qb);

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $iterator = new ScheduledForExportContactIterator($addressBook, $registry);
        $iterator->setBatchSize($batchSize);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
