<?php

namespace Oro\Bundle\DotmailerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;

class OroDotmailerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ContactExportQueryBuilderAdapterCompilerPath());
    }
}
