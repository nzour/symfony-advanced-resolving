<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Attribute;

use Attribute;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class FromQuery
{
    public function __construct(
        public ?string $paramName = null,
        /**
         * Set's {@see AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT} when denormalize object from query-params
         */
        public bool $disableTypeEnforcement = true,
    ) {
    }
}
