<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\DotmailerBundle\Model\ForceSyncEvent;

class ForceSyncEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSet()
    {
        $event = new ForceSyncEvent(['classes']);
        $this->assertEquals(['classes'], $event->getClasses());

        $event->setClasses(['new classes']);
        $this->assertEquals(['new classes'], $event->getClasses());
    }
}
