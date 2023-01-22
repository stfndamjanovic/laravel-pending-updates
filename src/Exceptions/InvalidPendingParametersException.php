<?php

namespace Stfn\PendingUpdates\Exceptions;

use Exception;

class InvalidPendingParametersException extends Exception
{
    public static function pastTimestamp()
    {
        return new self('Timestamp is in the past. Please use only future dates to set postpone updates.');
    }

    public static function negativeTimeConfiguration()
    {
        return new self('Cannot use below 0 value.');
    }

    public static function twicePropertySet()
    {
        return new self('Cannot set postpone property twice.');
    }

    public static function invalidStartAtConfiguration()
    {
        return new self('Invalid start at configuration. Cannot use start at and delay for at the same time.');
    }

    public static function invalidRevertAtConfiguration()
    {
        return new self('Invalid revert at configuration. Cannot use revert at and keep for at the same time.');
    }

    public static function invalidCombinationOfStartAndRevertAt()
    {
        return new self('Invalid timestamp configuration. Start at cannot be grater than revert at.');
    }

    public static function invalidTimestampConfiguration()
    {
        return new self('Invalid timestamp configuration. You must set at least one parameter.');
    }
}
