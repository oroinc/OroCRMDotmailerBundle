<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

class EnumValueStub extends EnumOption
{
    public function __construct($id, $name = '', $priority = 0, $default = false)
    {
        parent::__construct('test', $name, $id, $priority, $default);
    }
}
