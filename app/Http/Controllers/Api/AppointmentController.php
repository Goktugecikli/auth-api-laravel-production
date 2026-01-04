<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Support\ApiResponse;
use App\Exceptions\DomainException;
use App\Support\ErrorCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse, AuthorizesRequests; // ✅ authorize() buradan gelir

    public function __construct(private readonly AppointmentService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:read')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $q = $this->service->queryForUser($user);

        if ($request->filled('status')) {
            $q->where('status', (string) $request->query('status'));
        }

        if ($request->filled('provider_id')) {
            $q->where('provider_id', (int) $request->query('provider_id'));
        }

        if ($request->filled('from')) {
            $q->where('starts_at', '>=', (string) $request->query('from'));
        }

        if ($request->filled('to')) {
            $q->where('starts_at', '<=', (string) $request->query('to'));
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $items = $q->orderBy('starts_at', 'desc')->paginate($perPage);

        return $this->ok([
            'items' => AppointmentResource::collection($items),
            'meta'  => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:write')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $appointment = $this->service->create(
            user: $user,
            providerId: (int) $request->input('provider_id'),
            startsAt: $request->date('starts_at'),
            endsAt: $request->date('ends_at'),
            notes: $request->input('notes')
        );

        return $this->ok([
            'appointment' => new AppointmentResource($appointment),
        ], 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:read')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $this->authorize('view', $appointment);

        return $this->ok([
            'appointment' => new AppointmentResource($appointment),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:write')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $this->authorize('update', $appointment);

        $updated = $this->service->update(
            appointment: $appointment,
            providerId: (int) $request->input('provider_id'),
            startsAt: $request->date('starts_at'),
            endsAt: $request->date('ends_at'),
            notes: $request->input('notes')
        );

        return $this->ok([
            'appointment' => new AppointmentResource($updated),
        ]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:write')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $this->authorize('delete', $appointment);

        $this->service->delete($appointment);

        return $this->ok(['message' => 'Deleted']);
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:write')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $this->authorize('cancel', $appointment);

        $cancelled = $this->service->cancel($appointment);

        return $this->ok([
            'appointment' => new AppointmentResource($cancelled),
        ]);
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('appointments:write')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        $this->authorize('complete', $appointment);

        $completed = $this->service->complete($appointment);

        return $this->ok([
            'appointment' => new AppointmentResource($completed),
        ]);
    }
}
