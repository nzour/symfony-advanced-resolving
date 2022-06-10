<?php

declare(strict_types=1);

namespace Tests\Unit\Resolver;

use AdvancedResolving\Core\Attribute\FromQuery;
use AdvancedResolving\Core\Exception\CouldNotCreateInstanceFromQueryParamsException;
use AdvancedResolving\Core\Exception\NonNullableArgumentWithNoDefaultValueFromQueryParamsException;
use AdvancedResolving\Core\Resolver\FromQueryMetaResolver;
use Closure;
use Exception;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function PHPUnit\Framework\assertEquals;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FromQueryMetaResolverTest extends TestCase
{
    private FromQueryMetaResolver $resolver;
    private MockObject|DenormalizerInterface $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->resolver = new FromQueryMetaResolver($this->denormalizer);
    }

    /**
     * @dataProvider provideDifferentCases
     */
    public function test(
        Request          $request,
        ArgumentMetadata $argument,
        FromQuery        $attribute,
        Closure          $denormalizer,
        mixed            $expected,
    ): void
    {
        $denormalizer($this->denormalizer);

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        /**
         * @psalm-suppress MixedAssignment
         */
        $actual = $this->resolver->resolve($request, $argument, $attribute);

        if ($expected instanceof Exception) {
            self::fail("Test case expected exception {$expected}, but no exception was thrown");
        }

        assertEquals($expected, $actual);
    }

    /**
     * @psalm-type Denormalizer = MockObject|DenormalizerInterface
     *
     * @psalm-type DataProviderItem = array{
     *     request: Request,
     *     argument: ArgumentMetadata,
     *     attribute: FromQuery,
     *     denormalizer: Closure(Denormalizer): mixed,
     *     expect: mixed,
     * }
     *
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress MixedMethodCall
     *
     * @return Generator<string, DataProviderItem>
     */
    public function provideDifferentCases(): Generator
    {
        $argumentName = 'doesNotMatter';
        $expect = new stdClass();

        yield 'Expect denormalize class from query-parameters successfully' => [
            'request' => new Request(query: []), // deserializes anyway
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->method('denormalize')->willReturn($expect),
            'expect' => $expect,
        ];

        $expect = new stdClass();

        yield 'Expect denormalizing class would return null, but argument got default value' => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: true,
                defaultValue: $expect,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->method('denormalize')->willReturn(null),
            'expect' => $expect,
        ];

        yield 'Expect denormalizing class would return null, and argument has no default value, but is nullable' => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: true,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->method('denormalize')->willReturn(null),
            'expect' => null,
        ];

        $exception = new CouldNotCreateInstanceFromQueryParamsException(stdClass::class);
        $exceptionClass = $exception::class;

        yield "Expect denormalizing class would return null, and argument have no default value, and also is not nullable. {$exceptionClass} should be thrown" => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->method('denormalize')->willReturn(null),
            'expect' => $exception,
        ];

        $expect = 'value';

        yield 'Expect extracting param by it\'s name with non-null value' => [
            'request' => new Request(query: [$argumentName => $expect]), // $argumentName is not preset, but there is $paramName, that is defined via FromQuery attribute
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: 'string',
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->expects(self::never())->method('denormalize'),
            'expect' => $expect,
        ];

        $paramName = 'anotherParamName';

        yield 'Expect extracting param by attribute\'s name with non-null value' => [
            'request' => new Request(query: [$paramName => $expect]),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: 'string',
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: false,
            ),
            'attribute' => new FromQuery($paramName),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->expects(self::never())->method('denormalize'),
            'expect' => $expect,
        ];

        $expect = 'default value';

        yield 'Expect extracting param from query with null value, but got default value' => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: 'string',
                isVariadic: false,
                hasDefaultValue: true,
                defaultValue: $expect,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->expects(self::never())->method('denormalize'),
            'expect' => $expect,
        ];


        yield 'Expect extracting param from query with null value, and no default value, but nullable' => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: 'string',
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: true,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->expects(self::never())->method('denormalize'),
            'expect' => null,
        ];

        $exception = new NonNullableArgumentWithNoDefaultValueFromQueryParamsException($argumentName);
        $exceptionClass = $exception::class;

        yield "Expect extracting param from query, and no default value, and also non-nullable type, {$exceptionClass} should be thrown" => [
            'request' => new Request(query: []),
            'argument' => new ArgumentMetadata(
                name: $argumentName,
                type: 'string',
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
                isNullable: false,
            ),
            'attribute' => new FromQuery(),
            'denormalizer' => fn(MockObject|DenormalizerInterface $d) => $d->expects(self::never())->method('denormalize'),
            'expect' => $exception,
        ];
    }
}
