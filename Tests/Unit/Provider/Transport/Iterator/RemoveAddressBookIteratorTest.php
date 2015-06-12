<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveAddressBookIterator;

class RemoveAddressBookIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $keepAddressBooks = [
            42,
            53
        ];
        $expectedItems = [
            $firstItem = ['id' => 23],
            $secondItem = ['id' => 44],
            $thirdItem = ['id' => 144],
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
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->exactly(2))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->at(0))
            ->method('execute')
            ->will($this->returnValue([$firstItem, $secondItem]));
        $query->expects($this->at(1))
            ->method('execute')
            ->will($this->returnValue([$thirdItem]));
        $repository = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->exactly(2))
            ->method('getAddressBooksForRemoveQB')
            ->with($channel, $keepAddressBooks)
            ->will($this->returnValue($qb));

        $registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $iterator = new RemoveAddressBookIterator($registry, $channel, $keepAddressBooks);
        $iterator->setBatchSize($batchSize);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
