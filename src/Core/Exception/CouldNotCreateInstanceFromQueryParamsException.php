<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Exception;

use Exception;

final class CouldNotCreateInstanceFromQueryParamsException extends Exception
{
    /**
     * @param class-string $fqcn
     */
    public function __construct(string $fqcn)
    {
        parent::__construct("Could not create instance of '{$fqcn}' from query parameters.");
    }
}
