<?php

namespace Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass for registering contact export query builder adapters.
 *
 * Collects and registers all services tagged with `oro_dotmailer.contact_export_qb_adapter` into the adapter registry.
 */
class ContactExportQueryBuilderAdapterCompilerPath implements CompilerPassInterface
{
    public const ADAPTERS_TAG = 'oro_dotmailer.contact.export.query_builder_adapter';
    public const REGISTRY = 'oro_dotmailer.contact.export.query_builder_adapter.registry';
    public const ADD_ADAPTER_METHOD = 'addAdapter';

    #[\Override]
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
