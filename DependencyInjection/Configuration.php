<?php

namespace Oro\Bundle\DotmailerBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\DotmailerBundle\Model\SyncManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_dotmailer');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'datafields_sync_interval' => ['value' => '1 day'],
                'force_sync_for_virtual_fields' => ['value' => SyncManager::FORCE_SYNC_VIRTUALS_ONLY],
            ]
        );

        return $treeBuilder;
    }
}
