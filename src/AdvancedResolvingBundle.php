<?php

declare(strict_types=1);

namespace AdvancedResolving;

use AdvancedResolving\DependencyInjection\AdvancedResolvingExtension;
use AdvancedResolving\DependencyInjection\LookupMetaResolversCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;


final class AdvancedResolvingBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LookupMetaResolversCompilerPass());
    }

    public function getContainerExtension(): AdvancedResolvingExtension
    {
        return new AdvancedResolvingExtension();
    }
}
