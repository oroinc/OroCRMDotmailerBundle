<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\EventListener\DataFieldRemoveListener;
use OroCRM\Bundle\DotmailerBundle\Exception\RestClientException;

class DataFieldRemoveListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dataFieldManager;

    /** @var DataFieldRemoveListener */
    protected $listener;

    protected function setUp()
    {
        $this->dataFieldManager = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Model\DataFieldManager')
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
        $this->listener->preRemove($args);
    }

    public function testPreRemoveWithException()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->throwException(new RestClientException()));
        $this->setExpectedException(
            'OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException',
            'The field cannot be removed.'
        );
        $this->listener->preRemove($args);
    }

    public function testPreRemoveWithFalseResult()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->returnValue(['result' => 'false']));
        $this->setExpectedException(
            'OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException',
            'The field cannot be removed. It is in use elsewhere in the system.'
        );
        $this->listener->preRemove($args);
    }

    public function testPreRemoveWithTrueResult()
    {
        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entity  = new DataField();
        $args = new LifecycleEventArgs($entity, $objectManager);
        $this->dataFieldManager->expects($this->once())->method('removeOriginDataField')->with($entity)
            ->will($this->returnValue(['result' => 'true']));
        $this->listener->preRemove($args);
    }
}
