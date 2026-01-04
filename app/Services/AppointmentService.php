<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Appointment;
use App\Models\User;
use App\Support\ErrorCode;
use Carbon\Carbon;

final class AppointmentService
{
    /**
     * Liste (Query builder döndürüyoruz, controller paginate eder)
     */
    public function queryForUser(User $user)
    {
        return Appointment::query()->where('user_id', $user->id);
    }

    /**
     * Create
     */
    public function create(
        User $user,
        int $providerId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $notes = null
    ): Appointment {
        $this->assertNoConflict(
            providerId: $providerId,
            startsAt: $startsAt,
            endsAt: $endsAt,
            ignoreAppointmentId: null
        );

        return Appointment::query()->create([
            'user_id'     => $user->id,
            'provider_id' => $providerId,
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'status'      => 'scheduled',
            'notes'       => $notes,
        ]);
    }

    /**
     * Update (reschedule + notes)
     * - sadece scheduled iken update edilebilir
     * - overlap kontrolü yapılır
     */
    public function update(
        Appointment $appointment,
        int $providerId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $notes = null
    ): Appointment {
        $this->assertStatusIsScheduled($appointment);

        $this->assertNoConflict(
            providerId: $providerId,
            startsAt: $startsAt,
            endsAt: $endsAt,
            ignoreAppointmentId: $appointment->id
        );

        $appointment->update([
            'provider_id' => $providerId,
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'notes'       => $notes,
        ]);

        return $appointment->refresh();
    }

    /**
     * Cancel
     * - sadece scheduled iken cancel edilebilir
     */
    public function cancel(Appointment $appointment): Appointment
    {
        $this->assertStatusIsScheduled($appointment);

        $appointment->update(['status' => 'cancelled']);

        return $appointment->refresh();
    }

    /**
     * Complete
     * - sadece scheduled iken completed yapılabilir
     */
    public function complete(Appointment $appointment): Appointment
    {
        $this->assertStatusIsScheduled($appointment);

        $appointment->update(['status' => 'completed']);

        return $appointment->refresh();
    }

    /**
     * Delete
     * - scheduled veya cancelled silinebilir
     * - completed silinmesin (istersen değiştirirsin)
     */
    public function delete(Appointment $appointment): void
    {
        if ($appointment->status === 'completed') {
            throw new DomainException(ErrorCode::APPOINTMENT_INVALID_STATUS);
        }

        $appointment->delete();
    }

    /**
     * -----------------------
     * Private helpers
     * -----------------------
     */

    private function assertStatusIsScheduled(Appointment $appointment): void
    {
        if ($appointment->status !== 'scheduled') {
            throw new DomainException(ErrorCode::APPOINTMENT_INVALID_STATUS);
        }
    }

    /**
     * Overlap kuralı:
     * Aynı provider için scheduled randevular arasında
     * [starts_at, ends_at) aralığı çakışmasın.
     */
    private function assertNoConflict(
        int $providerId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $ignoreAppointmentId
    ): void {
        $q = Appointment::query()
            ->where('provider_id', $providerId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($ignoreAppointmentId) {
            $q->where('id', '!=', $ignoreAppointmentId);
        }

        if ($q->exists()) {
            throw new DomainException(ErrorCode::APPOINTMENT_CONFLICT);
        }
    }
}
