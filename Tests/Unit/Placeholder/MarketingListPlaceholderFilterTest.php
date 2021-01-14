<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DotmailerBundle\Placeholders\MarketingListPlaceholderFilter;

class MarketingListPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MarketingListPlaceholderFilter
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->repository = $this->createMock('Doctrine\Persistence\ObjectRepository');

        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->target = new MarketingListPlaceholderFilter($this->registry);
    }

    public function testIsApplicableOnMarketingList()
    {
        $actual = $this->target->isApplicableOnMarketingList(new \StdClass());
        $this->assertFalse($actual);

        $entity = $this->createMock('Oro\Bundle\MarketingListBundle\Entity\MarketingList');
        $this->repository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with(['marketingList' => $entity])
            ->will($this->returnValue(false));
        $this->repository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with(['marketingList' => $entity])
            ->will($this->returnValue(true));

        $actual = $this->target->isApplicableOnMarketingList($entity);
        $this->assertFalse($actual);

        $actual = $this->target->isApplicableOnMarketingList($entity);
        $this->assertTrue($actual);
    }
}
