<?php

namespace Stfn\PostponeUpdates\Exceptions;

use Exception;

class InvalidPostponedUpdateModel extends Exception
{
    public static function create(string $model): self
    {
        return new self("The model `{$model}` is invalid. A valid model must extend the model \Stfn\PostponeUpdates\Models\PostponedUpdate.");
    }
}
