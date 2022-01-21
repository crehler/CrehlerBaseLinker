<?php

namespace Crehler\BaseLinkerShopsApi\Exception;

use Throwable;

class ProductNotFoundException extends CrehlerBaseLinkerException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = "Product with ID: $message not found.";
        parent::__construct($message, $code, $previous);
    }
}
