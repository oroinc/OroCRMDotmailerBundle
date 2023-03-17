<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContactExportQueryBuilderAdapterCompilerPathTest extends \PHPUnit\Framework\TestCase
{
    private ContactExportQueryBuilderAdapterCompilerPath $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ContactExportQueryBuilderAdapterCompilerPath();
    }

    public function testProcessDoNothingIfServicesNotFound()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_dotmailer.contact.export.query_builder_adapter.registry');

        $container->register('adapter_1')
            ->addTag('oro_dotmailer.contact.export.query_builder_adapter', ['priority' => 100]);
        $container->register('adapter_2')
            ->addTag('oro_dotmailer.contact.export.query_builder_adapter');
        $container->register('adapter_3')
            ->addTag('oro_dotmailer.contact.export.query_builder_adapter', ['priority' => -100]);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addAdapter', [new Reference('adapter_1'), 100]],
                ['addAdapter', [new Reference('adapter_2'), 0]],
                ['addAdapter', [new Reference('adapter_3'), -100]]
            ],
            $registryDef->getMethodCalls()
        );
    }
}
