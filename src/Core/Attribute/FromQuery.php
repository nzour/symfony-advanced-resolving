<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Attribute;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class FromQuery
{
    public function __construct(
        public ?string $paramName = null
    ) {
    }
}
