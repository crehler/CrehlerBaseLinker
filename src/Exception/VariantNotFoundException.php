<?php

namespace Crehler\BaseLinkerShopsApi\Exception;

use Throwable;

class VariantNotFoundException extends CrehlerBaseLinkerException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = "VariantReader with ID $message not found";
        parent::__construct($message, $code, $previous);
    }
}
