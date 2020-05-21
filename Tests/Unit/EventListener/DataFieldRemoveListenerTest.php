<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\EventListener\DataFieldRemoveListener;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;

class DataFieldRemoveListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dataFieldManager;

    /** @var DataFieldRemoveListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->dataFieldManager = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Model\DataFieldManager')
            ->disableOriginalConstructor()->getMock();
        $this->listener = new DataFieldRemoveListener($this->dataFieldManager);
    }

    public function testPreRemoveWithForceRemoveFlag()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $entity->setForceRemove(true);
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->never())->method('removeOriginDataField');
        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithException()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->throwException(new RestClientException()));
        $this->expectException(\Oro\Bundle\DotmailerBundle\Exception\RuntimeException::class);
        $this->expectExceptionMessage('The field cannot be removed.');
        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithFalseResult()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->returnValue(['result' => 'false']));
        $this->expectException(\Oro\Bundle\DotmailerBundle\Exception\RuntimeException::class);
        $this->expectExceptionMessage('The field cannot be removed. It is in use elsewhere in the system.');
        $this->listener->preRemove($entity, $args);
    }

    public function testPreRemoveWithTrueResult()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->returnValue(['result' => 'true']));
        $this->listener->preRemove($entity, $args);
    }
}
