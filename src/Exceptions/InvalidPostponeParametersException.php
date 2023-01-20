<?php

namespace Stfn\PostponeUpdates\Exceptions;

use Exception;

class InvalidPostponeParametersException extends Exception
{
    public static function create(): self
    {
        return new self('Invalid postpone parameters. Please check the package documentation.');
    }
}
