<?php

namespace Stfn\PendingUpdates\Exceptions;

use Exception;

class InvalidPendingParametersException extends Exception
{
    public static function create(): self
    {
        return new self('Invalid pending parameters. Please check the package documentation.');
    }
}
