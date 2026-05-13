<?php

namespace App\Modules\AdminCompany\Exceptions;

use Exception;

/**
 * Create-flow conflicts that require an explicit confirmation flag from the client.
 */
class ExecutiveConflictNeedsConfirmation extends Exception
{
    /** @param  array<string, mixed>  $context */
    public function __construct(
        public string $conflictCode,
        string $message,
        public array $context = []
    ) {
        parent::__construct($message);
    }
}
