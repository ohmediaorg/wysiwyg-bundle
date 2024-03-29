<?php

namespace OHMedia\WysiwygBundle;

use OHMedia\WysiwygBundle\DependencyInjection\Compiler\WysiwygPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OHMediaWysiwygBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new WysiwygPass());
    }
}
