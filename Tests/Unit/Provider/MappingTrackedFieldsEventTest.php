<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider;

use OroCRM\Bundle\DotmailerBundle\Provider\MappingTrackedFieldsEvent;

class MappingTrackedFieldsEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSet()
    {
        $event = new MappingTrackedFieldsEvent(['fields']);
        $this->assertEquals($event->getFields(), ['fields']);
        $event->setFields(['new fields']);
        $this->assertEquals($event->getFields(), ['new fields']);
    }
}
