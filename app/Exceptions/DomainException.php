<?php

namespace App\Exceptions;

use App\Support\ErrorCatalog;
use App\Support\ErrorCode;
use Exception;

class DomainException extends Exception
{
    public function __construct(
        public readonly ErrorCode $codeEnum,
        public readonly array $details = [],
        ?string $messageOverride = null
    ) {
        parent::__construct($messageOverride ?? ErrorCatalog::message($codeEnum));
    }

    public function status(): int
    {
        return ErrorCatalog::status($this->codeEnum);
    }

    public function codeKey(): string
    {
        return $this->codeEnum->value;
    }

    public function render($request)
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->codeKey(),
                'message' => $this->getMessage(),
                'details' => $this->details ?: null,
            ],
        ], $this->status());
    }

}
