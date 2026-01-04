<?php

namespace App\Support;

final class ErrorCatalog
{
    public static function status(ErrorCode $code): int
    {
        return match ($code) {

            // Auth
            ErrorCode::AUTH_INVALID => 401,
            ErrorCode::TOKEN_FORBIDDEN => 403,
            ErrorCode::USER_NOT_FOUND => 404,

            // Validation
            ErrorCode::VALIDATION_ERROR => 422,

            // Appointment
            ErrorCode::APPOINTMENT_CONFLICT => 409,
            ErrorCode::APPOINTMENT_FORBIDDEN => 403,
            ErrorCode::APPOINTMENT_NOT_FOUND => 404,
            ErrorCode::APPOINTMENT_INVALID_STATUS => 409,
        };
    }

    public static function message(ErrorCode $code): string
    {
        return match ($code) {

            // Auth
            ErrorCode::AUTH_INVALID => 'Invalid credentials',
            ErrorCode::TOKEN_FORBIDDEN => 'Token missing required ability',
            ErrorCode::USER_NOT_FOUND => 'User not found',

            // Validation
            ErrorCode::VALIDATION_ERROR => 'Validation failed',

            // Appointment
            ErrorCode::APPOINTMENT_CONFLICT =>
                'The selected time slot is not available',

            ErrorCode::APPOINTMENT_FORBIDDEN =>
                'You are not allowed to access this appointment',

            ErrorCode::APPOINTMENT_NOT_FOUND =>
                'Appointment not found',

            ErrorCode::APPOINTMENT_INVALID_STATUS =>
                'This appointment cannot be modified in its current status',
        };
    }
}
