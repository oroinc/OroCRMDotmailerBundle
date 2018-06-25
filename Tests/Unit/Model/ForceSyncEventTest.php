<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\DotmailerBundle\Model\ForceSyncEvent;

class ForceSyncEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSet()
    {
        $event = new ForceSyncEvent(['classes']);
        $this->assertEquals($event->getClasses(), ['classes']);
        $event->setClasses(['new classes']);
        $this->assertEquals($event->getClasses(), ['new classes']);
    }
}
