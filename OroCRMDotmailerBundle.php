<?php

namespace OroCRM\Bundle\DotmailerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroCRM\Bundle\DotmailerBundle\DependencyInjection\CompilerPath\ExportQueryBuilderProviderCompilerPath;

class OroCRMDotmailerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExportQueryBuilderProviderCompilerPath());
    }
}
