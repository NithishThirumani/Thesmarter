<?php

namespace App\Modules\AdminProducts\Exceptions;

use Exception;

final class ProductTemplateException extends Exception
{
    public function __construct(string $message = '', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
