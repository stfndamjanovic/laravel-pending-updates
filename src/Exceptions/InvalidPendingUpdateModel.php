<?php

namespace Stfn\PendingUpdates\Exceptions;

use Exception;

class InvalidPendingUpdateModel extends Exception
{
    public static function create(string $model): self
    {
        return new self("The model `{$model}` is invalid. A valid model must extend the model \Stfn\PendingUpdates\Models\PendingUpdate.");
    }
}
