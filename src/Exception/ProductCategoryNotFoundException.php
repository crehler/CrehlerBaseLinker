<?php

namespace Crehler\BaseLinkerShopsApi\Exception;

class ProductCategoryNotFoundException extends CrehlerBaseLinkerException
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        if (is_array($message)) {
            $message = print_r($message, true);
        }
        $message = "Not found category for product in BaseLinker for " . $message;
        parent::__construct($message, $code, $previous);
    }
}
