<?php

namespace Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContactExportQueryBuilderAdapterCompilerPath implements CompilerPassInterface
{
    const ADAPTERS_TAG = 'oro_dotmailer.contact.export.query_builder_adapter';
    const REGISTRY = 'oro_dotmailer.contact.export.query_builder_adapter.registry';
    const ADD_ADAPTER_METHOD = 'addAdapter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $adapters = $container->findTaggedServiceIds(self::ADAPTERS_TAG);
        if (!empty($adapters)) {
            $definition = $container->getDefinition(self::REGISTRY);
            foreach ($adapters as $id => $tags) {
                foreach ($tags as $tag) {
                    $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                    $definition->addMethodCall(self::ADD_ADAPTER_METHOD, [new Reference($id), $priority]);
                }
            }
        }
    }
}
