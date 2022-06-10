<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @template TAttribute
 */
interface MetaResolverInterface
{
    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @param TAttribute $attribute
     *
     * @return mixed
     */
    public function resolve(Request $request, ArgumentMetadata $argument, mixed $attribute): mixed;

    /**
     * @return class-string<TAttribute>
     */
    public static function supportedAttribute(): string;
}
