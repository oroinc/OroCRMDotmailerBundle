<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\Repository\CampaignRepository;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemoveCampaignIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class RemoveCampaignIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $channel = $this->createMock(Channel::class);
        $keepCampaigns = [
            42,
            53
        ];
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];
        $thirdItem = ['id' => 144];
        $expectedItems = [
            $firstItem,
            $secondItem,
            $thirdItem,
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
        $repository = $this->createMock(CampaignRepository::class);
        $repository->expects($this->exactly(2))
            ->method('getCampaignsForRemoveQB')
            ->with($channel, $keepCampaigns)
            ->willReturn($qb);

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $iterator = new RemoveCampaignIterator($registry, $channel, $keepCampaigns);
        $iterator->setBatchSize($batchSize);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
