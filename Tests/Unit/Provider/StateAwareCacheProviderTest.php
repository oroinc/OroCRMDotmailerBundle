<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\StateAwareCacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;

class StateAwareCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var StateAwareCacheProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new StateAwareCacheProvider($this->doctrineHelper);
    }

    public function testHitMissing()
    {
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getCachedItem('scope', 'item'));
    }

    public function testHitNotAnObject()
    {
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->provider->setCachedItem('scope', 'item', ['array']);

        $this->assertEquals(['array'], $this->provider->getCachedItem('scope', 'item'));
    }

    public function testHitNonManageable()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('isManageableEntity')
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('refreshIncludingUnitializedRelations');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $value = new \stdClass();

        $this->provider->setCachedItem('scope', 'item', $value);

        $this->assertSame($value, $this->provider->getCachedItem('scope', 'item'));
    }

    public function testHitNewEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('isManageableEntity')
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('refreshIncludingUnitializedRelations');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $value = new AddressBook();

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityState')
            ->with($value)
            ->willReturn(UnitOfWork::STATE_NEW);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->provider->setCachedItem('scope', 'item', $value);

        $this->assertSame($value, $this->provider->getCachedItem('scope', 'item'));
    }

    public function testHitExistingEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(AddressBook::class);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('isManageableEntity')
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('refreshIncludingUnitializedRelations');

        $value = new AddressBook();
        ReflectionUtil::setId($value, 1);

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityState')
            ->with($value)
            ->willReturn(UnitOfWork::STATE_MANAGED);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($value);

        $this->provider->setCachedItem('scope', 'item', $value);

        $this->assertSame($value, $this->provider->getCachedItem('scope', 'item'));
    }

    public function testGetDetachedEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('isManageableEntity')
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('refreshIncludingUnitializedRelations');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $value = new AddressBook();

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityState')
            ->with($value)
            ->willReturn(UnitOfWork::STATE_DETACHED);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->provider->setCachedItem('scope', 'item', $value);

        $this->assertSame($value, $this->provider->getCachedItem('scope', 'item'));
    }
}
