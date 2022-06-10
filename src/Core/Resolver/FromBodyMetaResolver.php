<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Resolver;

use AdvancedResolving\Core\Attribute\FromBody;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements MetaResolverInterface<FromBody>
 */
final class FromBodyMetaResolver implements MetaResolverInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @param FromBody $attribute
     * @return mixed
     */
    public function resolve(Request $request, ArgumentMetadata $argument, mixed $attribute): mixed
    {
        $typehint = $argument->getType();

        if (null === $typehint || !class_exists($typehint)) {
            return null;
        }

        $content = $request->getContent(asResource: false);

        return $this->serializer->deserialize($content, $typehint, $attribute->format);
    }

    public static function supportedAttribute(): string
    {
        return FromBody::class;
    }
}
