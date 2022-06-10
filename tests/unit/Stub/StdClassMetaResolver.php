<?php

declare(strict_types=1);

namespace Tests\Unit\Stub;

use AdvancedResolving\Core\Resolver\MetaResolverInterface;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @implements MetaResolverInterface<stdClass>
 * @psalm-immutable
 * @internal
 */
class StdClassMetaResolver implements MetaResolverInterface
{
    /**
     * @psalm-allow-private-mutation
     */
    public int $resolveMethodCalledTimes = 0;

    public function __construct(private mixed $valueToResolve = null)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument, mixed $attribute): mixed
    {
        ++$this->resolveMethodCalledTimes;

        return $this->valueToResolve;
    }

    public static function supportedAttribute(): string
    {
        return stdClass::class;
    }
}
