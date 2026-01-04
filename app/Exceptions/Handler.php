<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $requestId = $request->attributes->get('request_id');

            if ($e instanceof \App\Exceptions\DomainException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $e->codeKey(),
                        'message' => $e->getMessage(),
                        'details' => $e->details ?: null,
                        'request_id' => $requestId,
                    ],
                ], $e->status());
            }

            return null;
        });
    }
}
