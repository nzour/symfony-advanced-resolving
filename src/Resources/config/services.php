<?php

declare(strict_types=1);

use AdvancedResolving\Command\DebugMetaResolversCommand;
use AdvancedResolving\Core\Internal\MainMetaResolver;
use AdvancedResolving\DependencyInjection\LookupMetaResolversCompilerPass;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services
        ->load("AdvancedResolving\\Core\\Resolver\\", '../../Resolver/')
        ->tag(LookupMetaResolversCompilerPass::META_RESOLVER_TAG);

    $services->set(DebugMetaResolversCommand::class);

    $services->set(MainMetaResolver::class)
        ->arg('$resolverInstances', tagged_iterator(LookupMetaResolversCompilerPass::META_RESOLVER_TAG));
};
