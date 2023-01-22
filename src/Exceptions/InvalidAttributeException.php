<?php

namespace Stfn\PendingUpdates\Exceptions;

use Exception;

class InvalidAttributeException extends Exception
{
    public static function create()
    {
        return new self('Trying to postpone update of not allowed attribute.');
    }
}
