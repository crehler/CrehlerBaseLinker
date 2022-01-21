<?php

namespace Crehler\BaseLinkerShopsApi\Exception;

use Throwable;

class ArticleForVariantNotFoundException extends CrehlerBaseLinkerException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = "The article must be sent before the variant! VariantReader ID: $message";
        parent::__construct($message, $code, $previous);
    }
}
