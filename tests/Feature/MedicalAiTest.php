<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MedicalAiTest extends TestCase
{
    public function test_doctor_can_request_medical_ai_assistance_from_openai(): void
    {
        config([
            'chatbot.ai_provider' => 'openai',
            'chatbot.openai_api_key' => 'test-key',
            'chatbot.openai_url' => 'https://api.openai.test/v1/responses',
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'output_text' => 'Khả năng bệnh gợi ý: viêm phổi cộng đồng. Bác sĩ là người quyết định chẩn đoán cuối cùng.',
            ], 200),
        ]);

        $doctor = new User([
            'name' => 'Bác sĩ Minh An',
            'email' => 'doctor@example.test',
            'role' => 'doctor',
        ]);
        $doctor->id = 99;

        $response = $this->actingAs($doctor)->postJson(route('doctor.ai.assist'), [
            'mode' => 'diagnosis',
            'symptoms' => 'Sốt, ho, khó thở',
            'vital_signs' => 'SpO2 93%, nhiệt độ 38.5',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.source', 'openai');

        $this->assertStringContainsString(
            'viêm phổi cộng đồng',
            $response->json('data.answer')
        );

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['model'] ?? null) === config('chatbot.openai_model')
                && str_contains($payload['input'] ?? '', 'Sốt, ho, khó thở');
        });
    }

    public function test_doctor_can_request_medical_ai_assistance_from_gemini(): void
    {
        config([
            'chatbot.ai_provider' => 'gemini',
            'chatbot.gemini_api_key' => 'gemini-test-key',
            'chatbot.gemini_model' => 'gemini-2.5-flash',
            'chatbot.gemini_url' => 'https://generativelanguage.googleapis.test/v1beta/models',
        ]);

        Http::fake([
            'generativelanguage.googleapis.test/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Khả năng bệnh gợi ý: viêm phổi cộng đồng. Bác sĩ là người quyết định cuối cùng.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $doctor = new User([
            'name' => 'Bác sĩ Minh An',
            'email' => 'gemini-doctor@example.test',
            'role' => 'doctor',
        ]);
        $doctor->id = 103;

        $response = $this->actingAs($doctor)->postJson(route('doctor.ai.assist'), [
            'mode' => 'diagnosis',
            'symptoms' => 'Sốt, ho, khó thở',
            'vital_signs' => 'SpO2 93%, nhiệt độ 38.5',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.source', 'gemini');

        $this->assertStringContainsString('viêm phổi cộng đồng', $response->json('data.answer'));

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'gemini-test-key')
                && str_contains($request->url(), 'gemini-2.5-flash:generateContent')
                && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Sốt, ho, khó thở');
        });
    }

    public function test_medical_ai_requires_openai_key_configuration(): void
    {
        config([
            'chatbot.ai_provider' => 'openai',
            'chatbot.openai_api_key' => null,
        ]);

        $doctor = new User([
            'name' => 'Bác sĩ Minh An',
            'email' => 'doctor2@example.test',
            'role' => 'doctor',
        ]);
        $doctor->id = 101;

        $response = $this->actingAs($doctor)->postJson(route('doctor.ai.assist'), [
            'mode' => 'diagnosis',
            'symptoms' => 'Nam 58 tuổi, sốt 38.7, ho đờm vàng, đau ngực phải khi hít sâu, khó thở nhẹ',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.source', 'openai_unconfigured');

        $this->assertStringContainsString('OPENAI_API_KEY', $response->json('data.answer'));
    }

    public function test_medical_ai_returns_openai_error_when_api_fails(): void
    {
        config([
            'chatbot.ai_provider' => 'openai',
            'chatbot.openai_api_key' => 'test-key',
            'chatbot.openai_url' => 'https://api.openai.test/v1/responses',
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'error' => [
                    'message' => 'You exceeded your current quota.',
                    'type' => 'insufficient_quota',
                    'code' => 'insufficient_quota',
                ],
            ], 429),
        ]);

        $doctor = new User([
            'name' => 'Bác sĩ Minh An',
            'email' => 'doctor3@example.test',
            'role' => 'doctor',
        ]);
        $doctor->id = 102;

        $response = $this->actingAs($doctor)->postJson(route('doctor.ai.assist'), [
            'mode' => 'summary',
            'symptoms' => 'Đau ngực',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.source', 'openai_error');

        $this->assertStringContainsString('hết quota', $response->json('data.answer'));
    }

    public function test_patient_cannot_use_medical_ai_assistance(): void
    {
        $patient = new User([
            'name' => 'Người bệnh',
            'email' => 'patient@example.test',
            'role' => 'patient',
        ]);
        $patient->id = 100;

        $response = $this->actingAs($patient)->postJson(route('doctor.ai.assist'), [
            'mode' => 'summary',
            'symptoms' => 'Đau đầu',
        ]);

        $response->assertForbidden();
    }
}
