<?php

declare(strict_types=1);

namespace Tests\Unit\Resolver;

use AdvancedResolving\Core\Internal\MainMetaResolver;
use AdvancedResolving\Core\Internal\MetaResolverStorage;
use AdvancedResolving\Core\Resolver\MetaResolverInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Tests\Unit\Stub\StdClassMetaResolver;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress InternalClass
 * @psalm-suppress InternalMethod
 * @psalm-suppress InternalProperty
 * @psalm-suppress InaccessibleProperty
 * @psalm-suppress MixedAssignment
 */
class MainMetaResolverTest extends TestCase
{
    private MainMetaResolver $resolver;
    private MetaResolverStorage $storage;

    /**
     * @var iterable<array-key, MetaResolverInterface>
     */
    private iterable $resolverInstances = [];

    protected function setUp(): void
    {
        $this->storage = new MetaResolverStorage([]);
        $this->storage->resolvers = [];
        $this->resolverInstances = (fn() => yield from $this->resolverInstances)();

        $this->resolver = new MainMetaResolver(
            $this->storage,
            $this->resolverInstances
        );
    }

    public function test_supports_expectFalse_whenNoAttributesAndNoResolvers(): void
    {
        $this->storage->resolvers = [];

        $argument = new ArgumentMetadata(
            'doesNotMatter',
            'DoesNotMatter',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [],
        );

        $actual = $this->resolver->supports(new Request(), $argument);

        assertFalse($actual);
    }

    public function test_supports_expectFalse_whenNoResolversAtAll(): void
    {
        $this->storage->resolvers = [];

        $argument = new ArgumentMetadata(
            'doesNotMatter',
            'DoesNotMatter',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [new stdClass()], // stdClass is a valid attribute for now
        );

        $actual = $this->resolver->supports(new Request(), $argument);

        assertFalse($actual);
    }

    public function test_supports_expectFalse_whenNoResolversFoundForSpecifiedAttribute(): void
    {
        $this->storage->resolvers = [];

        $argument = new ArgumentMetadata(
            'doesNotMatter',
            'DoesNotMatter',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [new stdClass()],
        );

        $actual = $this->resolver->supports(new Request(), $argument);

        assertFalse($actual);
    }

    public function test_supports_expectTrue_whenThereIsAtLeastOneSupportedResolverDefined(): void
    {
        $this->storage->resolvers = [
            stdClass::class => MetaResolverInterface::class,
        ];

        $argument = new ArgumentMetadata(
            'doesNotMatter',
            'DoesNotMatter',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [new stdClass()],
        );

        $actual = $this->resolver->supports(new Request(), $argument);

        assertTrue($actual);
    }

    public function test_resolve_expectRuntimeException_whenNoAttributesAtAll(): void
    {
        $this->storage->resolvers = [
            stdClass::class => MetaResolverInterface::class
        ];

        $this->expectException(RuntimeException::class);

        $this->resolver
            ->resolve(
                new Request(),
                new ArgumentMetadata(
                    'doesNotMatter',
                    'DoesNotMatter',
                    isVariadic: false,
                    hasDefaultValue: false,
                    defaultValue: null,
                    isNullable: false,
                    attributes: [], // no attributes
                ),
            )
            ->current();
    }

    public function test_resolve_expectRuntimeException_whenAttributeIsNotSupported(): void
    {
        $this->storage->resolvers = []; // no resolvers

        $this->expectException(RuntimeException::class);

        $this->resolver
            ->resolve(
                new Request(),
                new ArgumentMetadata(
                    'doesNotMatter',
                    'DoesNotMatter',
                    isVariadic: false,
                    hasDefaultValue: false,
                    defaultValue: null,
                    isNullable: false,
                    attributes: [new stdClass()],
                ),
            )
            ->current();
    }

    public function test_resolve_expectResolveValue_whenResolverDefined(): void
    {
        $expectedValue = 'this is expected resolved value';
        $stdClassResolver = new StdClassMetaResolver($expectedValue);

        $this->storage->resolvers = [stdClass::class => StdClassMetaResolver::class];
        $this->resolverInstances = [$stdClassResolver];

        $actualValue = $this->resolver
            ->resolve(
                new Request(),
                new ArgumentMetadata(
                    'doesNotMatter',
                    'DoesNotMatter',
                    isVariadic: false,
                    hasDefaultValue: false,
                    defaultValue: null,
                    isNullable: false,
                    attributes: [new stdClass()],
                ),
            )
            ->current();

        assertEquals(1, $stdClassResolver->resolveMethodCalledTimes);
        assertEquals($expectedValue, $actualValue);
    }
}
