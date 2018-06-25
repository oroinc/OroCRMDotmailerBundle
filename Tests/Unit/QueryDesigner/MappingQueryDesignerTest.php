<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\DotmailerBundle\QueryDesigner\MappingQueryDesigner;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MappingQueryDesignerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testMethods()
    {
        static::assertPropertyAccessors(new MappingQueryDesigner(), [
            ['entity', 'string'],
            ['definition', 'string'],
        ]);
    }
}
