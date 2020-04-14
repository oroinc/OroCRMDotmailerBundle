<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;
use Symfony\Component\DependencyInjection\Reference;

class ContactExportQueryBuilderAdapterCompilerPathTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactExportQueryBuilderAdapterCompilerPath
     */
    protected $target;

    protected function setUp(): void
    {
        $this->target = new ContactExportQueryBuilderAdapterCompilerPath();
    }

    public function testProcessDoNothingIfServicesNotFound()
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ContactExportQueryBuilderAdapterCompilerPath::ADAPTERS_TAG)
            ->will($this->returnValue([]));
        $container->expects($this->never())
            ->method('getDefinition');

        $this->target->process($container);
    }

    public function testProcess()
    {
        $firstAdapterId = 'firstAdapterId';
        $secondAdapterId = 'secondAdapterId';
        $secondAdapterPriority = 200;

        $services = [
            $firstAdapterId => [[]],
            $secondAdapterId => [['priority' => $secondAdapterPriority]],
        ];

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ContactExportQueryBuilderAdapterCompilerPath::ADAPTERS_TAG)
            ->will($this->returnValue($services));
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ContactExportQueryBuilderAdapterCompilerPath::REGISTRY)
            ->will($this->returnValue($definition));

        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with(
                ContactExportQueryBuilderAdapterCompilerPath::ADD_ADAPTER_METHOD,
                [new Reference($firstAdapterId), 0]
            );
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with(
                ContactExportQueryBuilderAdapterCompilerPath::ADD_ADAPTER_METHOD,
                [new Reference($secondAdapterId), $secondAdapterPriority]
            );

        $this->target->process($container);
    }
}
