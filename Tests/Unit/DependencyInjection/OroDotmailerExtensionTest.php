<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DotmailerBundle\Controller\Api\Rest\DataFieldController;
use Oro\Bundle\DotmailerBundle\Controller\Api\Rest\DataFieldMappingController;
use Oro\Bundle\DotmailerBundle\DependencyInjection\OroDotmailerExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDotmailerExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroDotmailerExtension());

        $expectedDefinitions = [
            DataFieldController::class,
            DataFieldMappingController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
