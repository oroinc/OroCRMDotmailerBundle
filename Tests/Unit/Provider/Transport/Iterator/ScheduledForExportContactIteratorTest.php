<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

class ScheduledForExportContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->will($this->returnValue($addressBookOriginId = 234));

        $em = $this->createMock(EntityManagerInterface::class);
        $registry->expects($this->any())->method('getManagerForClass')->willReturn($em);
        $em->expects($this->any())->method('getReference')->willReturn($addressBook);

        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];
        $thirdItem = ['id' => 144];
        $expectedItems = [
            ['id' => 23, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 144, ScheduledForExportContactIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->exactly(2))
            ->method('setMaxResults')
            ->with($batchSize = 2)
            ->will($this->returnSelf());
        $qb->expects($this->exactly(2))
            ->method('setFirstResult')
            ->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['useQueryCache', 'getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->exactly(2))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $query->expects($this->exactly(2))
            ->method('useQueryCache')
            ->will($this->returnSelf());

        $getArrayResultMap = [
            [$firstItem, $secondItem],
            [$thirdItem]
        ];
        $query->expects($this->exactly(2))
            ->method('getArrayResult')
            ->will(
                $this->returnCallback(
                    function () use (&$getArrayResultMap) {
                        $result = current($getArrayResultMap);
                        next($getArrayResultMap);

                        return $result;
                    }
                )
            );
        $repository = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->exactly(2))
            ->method('getScheduledForExportByChannelQB')
            ->with($addressBook)
            ->will($this->returnValue($qb));

        $registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $iterator = new ScheduledForExportContactIterator($addressBook, $registry);
        $iterator->setBatchSize($batchSize);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
