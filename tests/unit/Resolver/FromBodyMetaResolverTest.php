<?php

declare(strict_types=1);

namespace Tests\Unit\Resolver;

use AdvancedResolving\Core\Attribute\FromBody;
use AdvancedResolving\Core\Resolver\FromBodyMetaResolver;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class FromBodyMetaResolverTest extends TestCase
{
    private MockObject|SerializerInterface $serializer;
    private FromBodyMetaResolver $resolver;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->resolver = new FromBodyMetaResolver($this->serializer);
    }

    public function test_expectReturnNull_whenParamGotNoTypehint(): void
    {
        $actual = $this->resolver->resolve(
            new Request(),
            new ArgumentMetadata(
                name: 'doesNotMatter',
                type: null,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null
            ),
            new FromBody()
        );

        assertNull($actual);
    }

    public function test_expectReturnNull_whenParamClassNotExists(): void
    {
        $typeFqcn = 'Foobar\\NonExisting';

        assertFalse(\class_exists($typeFqcn));

        $actual = $this->resolver->resolve(
            new Request(),
            new ArgumentMetadata(
                name: 'doesNotMatter',
                type: $typeFqcn,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null
            ),
            new FromBody()
        );

        assertNull($actual);
    }

    public function test_expectAnythingDeserializerReturn_whenSymfonySerializerSucceed(): void
    {
        $content = <<<JSON
            { "foobar": "barfoo" }
        JSON;

        // let's say serializer can accept deserializer stdClass
        $expected = new stdClass();
        $expected->foobar = 'barfoo';

        $this->serializer->method('deserialize')
            ->with($content, stdClass::class, FromBody::DEFAULT_FORMAT)
            ->willReturn($expected);

        $actual = $this->resolver->resolve(
            new Request(content: $content),
            new ArgumentMetadata(
                name: 'doesNotMatter',
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null
            ),
            new FromBody(),
        );

        assertTrue($actual instanceof stdClass);
        assertEquals($actual->foobar, "barfoo");
    }

    public function test_expectAnyExceptionThrown_whenSymfonySerializerFailures(): void
    {
        $content = <<<JSON
            { "foobar": "barfoo" }
        JSON;

        $expectedMessage = 'Something damn wrong happened to symfony serializer';

        $this->serializer->method('deserialize')
            ->willThrowException(new Exception($expectedMessage));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->resolver->resolve(
            new Request(content: $content),
            new ArgumentMetadata(
                name: 'doesNotMatter',
                type: stdClass::class,
                isVariadic: false,
                hasDefaultValue: false,
                defaultValue: null,
            ),
            new FromBody(),
        );
    }
}
