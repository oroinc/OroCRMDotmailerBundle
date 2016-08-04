<?php

namespace OroCRM\Bundle\DotmailerBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use OroCRM\Bundle\DotmailerBundle\Async\Topics;
use OroCRM\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
            ->add(Topics::EXPORT_CONTACTS_STATUS_UPDATE, '')
        ;

        $container->addCompilerPass($addTopicMetaPass);
    }
}
