<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Model;

use OroCRM\Bundle\DotmailerBundle\Model\ForceSyncEvent;

class ForceSyncEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSet()
    {
        $event = new ForceSyncEvent(['classes']);
        $this->assertEquals($event->getClasses(), ['classes']);
        $event->setClasses(['new classes']);
        $this->assertEquals($event->getClasses(), ['new classes']);
    }
}
