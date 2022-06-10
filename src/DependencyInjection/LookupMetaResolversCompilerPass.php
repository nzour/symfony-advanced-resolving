<?php

declare(strict_types=1);

namespace AdvancedResolving\DependencyInjection;

use AdvancedResolving\Core\Internal\MetaResolverStorage;
use AdvancedResolving\Core\Resolver\MetaResolverInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function array_key_exists;
use function array_keys;
use function is_a;

/**
 * @internal
 */
final class LookupMetaResolversCompilerPass implements CompilerPassInterface
{
    public const META_RESOLVER_TAG = 'meta-resolver';

    public function process(ContainerBuilder $container): void
    {
        $resolvers = $container->findTaggedServiceIds(self::META_RESOLVER_TAG);

        $map = self::getResolversMap($container, array_keys($resolvers));

        $container->setDefinition(MetaResolverStorage::class, new Definition(
            class: MetaResolverStorage::class,
            arguments: ['$resolvers' => $map]
        ));
    }

    /**
     * @param list<string> $resolvers
     *
     * @return array<class-string, class-string<MetaResolverInterface>>
     * @throws \Exception
     */
    public static function getResolversMap(ContainerBuilder $container, array $resolvers): array
    {
        $map = [];
        $duplicates = [];

        foreach ($resolvers as $resolverClass) {
            if (!is_a($resolverClass, MetaResolverInterface::class, allow_string: true)) {
                continue;
            }

            $attribute = $resolverClass::supportedAttribute();

            if (array_key_exists($attribute, $map)) {
                $duplicates[$attribute] = [
                    ...$duplicates[$attribute] ?? [],
                    $resolverClass,
                ];
            }

            $map[$attribute] = $resolverClass;
        }

        if ($duplicates !== []) {
            throw new RuntimeException('There are duplicated meta resolvers detected'); // todo expanded message
        }

        return $map;
    }
}
