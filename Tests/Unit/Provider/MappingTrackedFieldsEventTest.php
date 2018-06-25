<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\MappingTrackedFieldsEvent;

class MappingTrackedFieldsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSet()
    {
        $event = new MappingTrackedFieldsEvent(['fields']);
        $this->assertEquals($event->getFields(), ['fields']);
        $event->setFields(['new fields']);
        $this->assertEquals($event->getFields(), ['new fields']);
    }
}
