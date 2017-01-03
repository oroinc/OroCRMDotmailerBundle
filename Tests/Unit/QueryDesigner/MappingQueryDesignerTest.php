<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\QueryDesigner;

use OroCRM\Bundle\DotmailerBundle\QueryDesigner\MappingQueryDesigner;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MappingQueryDesignerTest extends \PHPUnit_Framework_TestCase
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
