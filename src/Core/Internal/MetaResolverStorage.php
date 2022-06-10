<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Internal;

use AdvancedResolving\Core\Resolver\MetaResolverInterface;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class MetaResolverStorage
{
    /**
     * @param array<class-string, class-string<MetaResolverInterface>> $resolvers
     */
    public function __construct(
        public array $resolvers,
    ) {
    }
}
