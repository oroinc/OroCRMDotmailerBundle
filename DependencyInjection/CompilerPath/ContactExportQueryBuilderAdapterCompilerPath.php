<?php

namespace OroCRM\Bundle\DotmailerBundle\DependencyInjection\CompilerPath;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContactExportQueryBuilderAdapterCompilerPath implements CompilerPassInterface
{
    const ADAPTERS_TAG = 'orocrm_dotmailer.contact.export.query_builder_adapter';
    const REGISTRY = 'orocrm_dotmailer.contact.export.query_builder_adapter.registry';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $adapters = $container->findTaggedServiceIds(self::ADAPTERS_TAG);
        if (!empty($adapters)) {
            $definition = $container->getDefinition(self::REGISTRY);
            foreach ($adapters as $id => $arguments) {
                $priority = isset($arguments['priority']) ? $arguments['priority'] : 0;
                $definition->addMethodCall('addAdapter', [new Reference($id), $priority]);
            }
        }
    }
}
