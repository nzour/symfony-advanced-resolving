<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Resolver;

use AdvancedResolving\Core\Attribute\FromQuery;
use AdvancedResolving\Core\Exception\CouldNotCreateInstanceFromQueryParamsException;
use AdvancedResolving\Core\Exception\NonNullableArgumentWithNoDefaultValueFromQueryParamsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function class_exists;
use function is_string;

/**
 * @implements MetaResolverInterface<FromQuery>
 */
final class FromQueryMetaResolver implements MetaResolverInterface
{
    private const BOOL_TYPE_NAME = 'bool';

    public function __construct(private DenormalizerInterface $denormalizer)
    {
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @param FromQuery $attribute
     *
     * @return mixed
     *
     * @throws CouldNotCreateInstanceFromQueryParamsException
     * @throws NonNullableArgumentWithNoDefaultValueFromQueryParamsException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function resolve(Request $request, ArgumentMetadata $argument, mixed $attribute): mixed
    {
        $queryParams = $request->query->all();

        $typehint = $argument->getType();
        $argumentName = $attribute->paramName ?? $argument->getName();

        if (self::isFqcn($typehint)) {
            $value = $this->denormalizer->denormalize($queryParams, $typehint);
        } else {
            /**
             * @var mixed $value
             */
            $value = $queryParams[$argumentName] ?? null;
        }

        if (null === $value) {
            if ($argument->hasDefaultValue()) {
                return $argument->getDefaultValue();
            }

            if (!$argument->isNullable()) {
                throw self::isFqcn($typehint)
                    ? new CouldNotCreateInstanceFromQueryParamsException($typehint)
                    : new NonNullableArgumentWithNoDefaultValueFromQueryParamsException($argumentName);
            }
        }

        return self::BOOL_TYPE_NAME === $argument->getType()
            ? self::convertToBool($value)
            : $value;
    }

    public static function supportedAttribute(): string
    {
        return FromQuery::class;
    }

    /**
     * @param string|null $maybeTypehint
     * @return bool
     *
     * @psalm-assert-if-true class-string $maybeTypehint
     */
    private static function isFqcn(?string $maybeTypehint): bool
    {
        return null !== $maybeTypehint && class_exists($maybeTypehint);
    }

    private static function convertToBool(mixed $value): bool
    {
        if (is_string($value)) {
            return $value !== 'false' && $value;
        }

        return (bool) $value;
    }
}
