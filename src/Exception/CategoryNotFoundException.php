<?php

namespace Crehler\BaseLinkerShopsApi\Exception;

class CategoryNotFoundException extends CrehlerBaseLinkerException
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        $message = "CategoryReader with ID: $message not found.";
        parent::__construct($message, $code, $previous);
    }
}
