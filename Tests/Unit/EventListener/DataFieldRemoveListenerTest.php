<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\EventListener\DataFieldRemoveListener;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;

class DataFieldRemoveListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataFieldManager|\PHPUnit\Framework\MockObject\MockObject */
    private $dataFieldManager;

    /** @var DataFieldRemoveListener */
    private $listener;

    protected function setUp(): void
    {
        $this->dataFieldManager = $this->createMock(DataFieldManager::class);

        $this->listener = new DataFieldRemoveListener($this->dataFieldManager);
    }

    public function testPreRemoveWithForceRemoveFlag()
    {
        $objectManager = $this->createMock(EntityManager::class);
        $entity = new DataField();
        $entity->setForceRemove(true);
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->never())
            ->method('removeOriginDataField');
        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithException()
    {
        $objectManager = $this->createMock(EntityManager::class);
        $entity = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())
            ->method('removeOriginDataField')
            ->with($entity)
            ->willThrowException(new RestClientException());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The field cannot be removed.');

        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithFalseResult()
    {
        $objectManager = $this->createMock(EntityManager::class);
        $entity = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())
            ->method('removeOriginDataField')
            ->with($entity)
            ->willReturn(['result' => 'false']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The field cannot be removed. It is in use elsewhere in the system.');

        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithTrueResult()
    {
        $objectManager = $this->createMock(EntityManager::class);
        $entity = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())
            ->method('removeOriginDataField')
            ->with($entity)
            ->willReturn(['result' => 'true']);
        $this->listener->preRemove($entity, $args);
    }
}
