<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DotmailerBundle\Placeholders\MarketingListPlaceholderFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

class MarketingListPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var MarketingListPlaceholderFilter */
    private $target;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->target = new MarketingListPlaceholderFilter($this->registry);
    }

    public function testIsApplicableOnMarketingList()
    {
        $actual = $this->target->isApplicableOnMarketingList(new \stdClass());
        $this->assertFalse($actual);

        $entity = $this->createMock(MarketingList::class);
        $this->repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['marketingList' => $entity])
            ->willReturnOnConsecutiveCalls(false, true);

        $actual = $this->target->isApplicableOnMarketingList($entity);
        $this->assertFalse($actual);

        $actual = $this->target->isApplicableOnMarketingList($entity);
        $this->assertTrue($actual);
    }
}
