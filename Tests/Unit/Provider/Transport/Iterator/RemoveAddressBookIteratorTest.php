<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveAddressBookIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class RemoveAddressBookIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $channel = $this->createMock(Channel::class);
        $keepAddressBooks = [
            42,
            53
        ];
        $expectedItems = [
            $firstItem = ['id' => 23],
            $secondItem = ['id' => 44],
            $thirdItem = ['id' => 144],
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->exactly(2))
            ->method('setMaxResults')
            ->with($batchSize = 2)
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setFirstResult')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->exactly(2))
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls([$firstItem, $secondItem], [$thirdItem]);
        $repository = $this->createMock(AddressBookRepository::class);
        $repository->expects($this->exactly(2))
            ->method('getAddressBooksForRemoveQB')
            ->with($channel, $keepAddressBooks)
            ->willReturn($qb);

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $iterator = new RemoveAddressBookIterator($registry, $channel, $keepAddressBooks);
        $iterator->setBatchSize($batchSize);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
