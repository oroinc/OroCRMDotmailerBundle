<?php

namespace Oro\Bundle\DotmailerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;

class OroCRMDotmailerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ContactExportQueryBuilderAdapterCompilerPath());

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass
            ->add(Topics::EXPORT_CONTACTS_STATUS_UPDATE)
        ;

        $container->addCompilerPass($addTopicMetaPass);
    }
}
