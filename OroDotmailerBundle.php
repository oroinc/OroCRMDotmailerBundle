<?php

namespace Oro\Bundle\DotmailerBundle;

use Oro\Bundle\DotmailerBundle\DependencyInjection\CompilerPass\ContactExportQueryBuilderAdapterCompilerPath;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The DotmailerBundle bundle class.
 */
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
