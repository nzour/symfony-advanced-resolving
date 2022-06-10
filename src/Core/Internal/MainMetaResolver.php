<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Internal;

use AdvancedResolving\Core\Resolver\MetaResolverInterface;
use Generator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use function array_key_exists;
use function array_values;

/**
 * @internal
 */
final class MainMetaResolver implements ArgumentValueResolverInterface
{
    /**
     * @param MetaResolverStorage $resolverStorage
     * @param iterable<array-key, MetaResolverInterface> $resolverInstances
     */
    public function __construct(private MetaResolverStorage $resolverStorage, private iterable $resolverInstances)
    {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        foreach ($argument->getAttributes() as $attribute) {
            if (array_key_exists($attribute::class, $this->resolverStorage->resolvers)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $resolverAndAttribute = $this->findFirstSupportedResolver($this->resolverStorage->resolvers, array_values($argument->getAttributes()));

        /**
         * In ideal world, this method must be executed only after method {@see MainMetaResolver::supports()} already has been executed, and returned true.
         * But we are living in real world, so we as well should check if resolving is actually supported.
         * And also, it's necessary be assured that an attribute, and it's resolver actually are not null.
         * This is why we got a RuntimeException here, so we are telling that it's uncommon situation and not supposed to be like this.
         */
        if (null === $resolverAndAttribute) {
            throw new RuntimeException('Unable to resolve value');
        }

        /**
         * @var MetaResolverInterface $resolver
         * @ignore-var
         */
        [$resolver, $attribute] = $resolverAndAttribute;

        yield $resolver->resolve($request, $argument, $attribute);
    }

    /**
     * @param array<class-string, class-string<MetaResolverInterface>> $resolvers
     * @param list<object> $attributes
     *
     * @return null|array{MetaResolverInterface, object}
     */
    private function findFirstSupportedResolver(array $resolvers, array $attributes): ?array
    {
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute::class, $resolvers)) {
                $resolverInstance = $this->findResolverInstance($resolvers[$attribute::class]);

                if (null === $resolverInstance) {
                    return null;
                }

                return [$resolverInstance, $attribute];
            }
        }

        return null;
    }

    /**
     * @param class-string<MetaResolverInterface> $resolverClass
     * @return MetaResolverInterface|null
     */
    private function findResolverInstance(string $resolverClass): ?MetaResolverInterface
    {
        static $cachedResolvers = null;

        if (null === $cachedResolvers) {
            $cachedResolvers = [];

            foreach ($this->resolverInstances as $resolver) {
                $cachedResolvers[$resolver::class] = $resolver;
            }
        }

        /**
         * @var array<class-string<MetaResolverInterface>, MetaResolverInterface> $cachedResolvers
         */
        return $cachedResolvers[$resolverClass] ?? null;
    }
}
