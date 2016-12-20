<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ChangedFieldLogTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var ChangedFieldLog
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new ChangedFieldLog();
    }

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['channelId', 2],
            ['parentEntity', 'testEntity'],
            ['relatedFieldPath', 'testPath'],
            ['relatedId', 12],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }
}
