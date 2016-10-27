<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class EnumValueStub extends AbstractEnumValue
{
    /**
     * @inheritdoc
     */
    public function __construct($id, $name = '', $priority = 0, $default = false)
    {
        parent::__construct($id, $name, $priority, $default);
    }
}
