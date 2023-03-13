<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DotmailerBundle\DependencyInjection\OroDotmailerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDotmailerExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroDotmailerExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'datafields_sync_interval' => ['value' => '1 day', 'scope' => 'app'],
                        'force_sync_for_virtual_fields' => ['value' => 'VirtualOnly', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_dotmailer')
        );
    }
}
