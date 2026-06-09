<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMedicalRecordManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite is required for database feature tests.');
        }

        parent::setUp();
    }

    public function test_doctor_only_sees_own_medical_records(): void
    {
        [$doctorUser, $doctor] = $this->doctor('doctor-one@example.test');
        [, $otherDoctor] = $this->doctor('doctor-two@example.test');
        $patient = User::factory()->create(['role' => 'patient']);

        $ownRecord = MedicalRecord::create([
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'examined_at' => now()->toDateString(),
            'diagnosis' => 'Hồ sơ thuộc bác sĩ đang đăng nhập',
        ]);

        MedicalRecord::create([
            'user_id' => $patient->id,
            'doctor_id' => $otherDoctor->id,
            'examined_at' => now()->toDateString(),
            'diagnosis' => 'Hồ sơ của bác sĩ khác',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.records.index'))
            ->assertOk()
            ->assertSee($ownRecord->diagnosis)
            ->assertDontSee('Hồ sơ của bác sĩ khác');
    }

    public function test_doctor_cannot_edit_another_doctors_medical_record(): void
    {
        [$doctorUser] = $this->doctor('doctor-one@example.test');
        [, $otherDoctor] = $this->doctor('doctor-two@example.test');

        $record = MedicalRecord::create([
            'doctor_id' => $otherDoctor->id,
            'examined_at' => now()->toDateString(),
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.records.edit', $record))
            ->assertForbidden();
    }

    public function test_doctor_can_create_record_from_own_appointment(): void
    {
        [$doctorUser, $doctor] = $this->doctor('doctor-one@example.test');
        $patient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::create([
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_name' => $patient->name,
            'patient_email' => $patient->email,
            'patient_phone' => '0900000000',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '08:00',
            'status' => 'completed',
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.records.store'), [
                'appointment_id' => $appointment->id,
                'examined_at' => now()->toDateString(),
                'symptoms' => 'Sốt và ho',
                'diagnosis' => 'Theo dõi viêm đường hô hấp',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('medical_records', [
            'appointment_id' => $appointment->id,
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Theo dõi viêm đường hô hấp',
        ]);
    }

    private function doctor(string $email): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => 'doctor',
        ]);

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $email,
            'is_active' => true,
        ]);

        return [$user->fresh('doctor'), $doctor];
    }
}
