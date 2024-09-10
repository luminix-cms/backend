<?php

namespace Luminix\Backend\Exceptions;

use Exception;

class InvalidFilterException extends Exception
{
    public function __construct($message = 'Invalid filter provided.', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

