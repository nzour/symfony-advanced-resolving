<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Exception;

use Exception;

final class NonNullableArgumentWithNoDefaultValueFromQueryParamsException extends Exception
{
    public function __construct(string $argumentName)
    {
        parent::__construct("Unable to resolve argument with name '{$argumentName}' from query parameters.");
    }
}
