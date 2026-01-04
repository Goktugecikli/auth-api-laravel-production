<?php

namespace Tests\Unit;

use App\Exceptions\DomainException;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use App\Support\ErrorCatalog;
use App\Support\ErrorCode;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppointmentService::class);

        Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assertDomainError(DomainException $e, ErrorCode $expected): void
    {
        $this->assertSame($expected->value, $e->codeKey());
        $this->assertSame(ErrorCatalog::status($expected), $e->status());
        $this->assertSame(ErrorCatalog::message($expected), $e->getMessage());
    }

    public function test_create_appointment_success_when_no_conflict(): void
    {
        $user = User::factory()->create();
        $provider = User::factory()->create();

        $appt = $this->service->create(
            user: $user,
            providerId: $provider->id,
            startsAt: Carbon::parse('2026-01-06 10:00:00'),
            endsAt: Carbon::parse('2026-01-06 10:30:00'),
            notes: 'kontrol'
        );

        $this->assertDatabaseHas('appointments', [
            'id'          => $appt->id,
            'user_id'     => $user->id,
            'provider_id' => $provider->id,
            'status'      => 'scheduled',
        ]);
    }

    public function test_create_throws_conflict_when_overlaps_existing_scheduled(): void
    {
        $user = User::factory()->create();
        $provider = User::factory()->create();

        Appointment::factory()->create([
            'provider_id' => $provider->id,
            'starts_at'   => Carbon::parse('2026-01-06 10:00:00'),
            'ends_at'     => Carbon::parse('2026-01-06 10:30:00'),
            'status'      => 'scheduled',
        ]);

        try {
            $this->service->create(
                user: $user,
                providerId: $provider->id,
                startsAt: Carbon::parse('2026-01-06 10:15:00'),
                endsAt: Carbon::parse('2026-01-06 10:45:00'),
                notes: null
            );

            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_CONFLICT);
        }
    }

    public function test_create_does_not_conflict_when_end_equals_other_start(): void
    {
        $user = User::factory()->create();
        $provider = User::factory()->create();

        Appointment::factory()->create([
            'provider_id' => $provider->id,
            'starts_at'   => Carbon::parse('2026-01-06 10:00:00'),
            'ends_at'     => Carbon::parse('2026-01-06 10:30:00'),
            'status'      => 'scheduled',
        ]);

        $appt = $this->service->create(
            user: $user,
            providerId: $provider->id,
            startsAt: Carbon::parse('2026-01-06 10:30:00'),
            endsAt: Carbon::parse('2026-01-06 11:00:00'),
            notes: null
        );

        $this->assertDatabaseHas('appointments', ['id' => $appt->id]);
    }

    public function test_update_throws_conflict_when_overlaps_other_appointment(): void
    {
        $user = User::factory()->create();
        $provider = User::factory()->create();

        $a1 = Appointment::factory()->create([
            'user_id'     => $user->id,
            'provider_id' => $provider->id,
            'starts_at'   => Carbon::parse('2026-01-06 10:00:00'),
            'ends_at'     => Carbon::parse('2026-01-06 10:30:00'),
            'status'      => 'scheduled',
        ]);

        Appointment::factory()->create([
            'provider_id' => $provider->id,
            'starts_at'   => Carbon::parse('2026-01-06 11:00:00'),
            'ends_at'     => Carbon::parse('2026-01-06 11:30:00'),
            'status'      => 'scheduled',
        ]);

        try {
            $this->service->update(
                appointment: $a1,
                providerId: $provider->id,
                startsAt: Carbon::parse('2026-01-06 11:15:00'),
                endsAt: Carbon::parse('2026-01-06 11:45:00'),
                notes: null
            );

            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_CONFLICT);
        }
    }

    public function test_update_not_allowed_when_not_scheduled(): void
    {
        $user = User::factory()->create();
        $provider = User::factory()->create();

        $appt = Appointment::factory()->create([
            'user_id'     => $user->id,
            'provider_id' => $provider->id,
            'starts_at'   => Carbon::parse('2026-01-06 10:00:00'),
            'ends_at'     => Carbon::parse('2026-01-06 10:30:00'),
            'status'      => 'cancelled',
        ]);

        try {
            $this->service->update(
                appointment: $appt,
                providerId: $provider->id,
                startsAt: Carbon::parse('2026-01-06 12:00:00'),
                endsAt: Carbon::parse('2026-01-06 12:30:00'),
                notes: 'x'
            );

            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_INVALID_STATUS);
        }
    }

    public function test_cancel_only_allowed_when_scheduled(): void
    {
        $appt = Appointment::factory()->cancelled()->create();

        try {
            $this->service->cancel($appt);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_INVALID_STATUS);
        }
    }

    public function test_complete_only_allowed_when_scheduled(): void
    {
        $appt = Appointment::factory()->cancelled()->create();

        try {
            $this->service->complete($appt);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_INVALID_STATUS);
        }
    }

    public function test_delete_not_allowed_when_completed(): void
    {
        $appt = Appointment::factory()->completed()->create();

        try {
            $this->service->delete($appt);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertDomainError($e, ErrorCode::APPOINTMENT_INVALID_STATUS);
        }
    }
}
