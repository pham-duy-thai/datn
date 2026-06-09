<?php

namespace App\Services;

use App\Models\MedicalImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MedicalImageAiService
{
    public function analyze(MedicalImage $medicalImage): MedicalImage
    {
        if (config('medical_imaging.ai_provider', 'gemini') === 'gemini') {
            return $this->analyzeWithGemini($medicalImage);
        }

        return $this->analyzeWithYolo($medicalImage);
    }

    private function analyzeWithYolo(MedicalImage $medicalImage): MedicalImage
    {
        $url = config('medical_imaging.ai_service_url');

        if (! $url) {
            return $this->markPending($medicalImage, 'Chưa cấu hình MEDICAL_IMAGE_AI_URL. Ảnh đã được lưu và chờ phân tích YOLO.');
        }

        $absolutePath = Storage::disk('public')->path($medicalImage->image_path);

        if (! is_file($absolutePath)) {
            return $this->markFailed($medicalImage, 'Không tìm thấy file ảnh đã upload.');
        }

        try {
            $response = Http::timeout((int) config('medical_imaging.timeout', 45))
                ->attach('image', fopen($absolutePath, 'r'), basename($absolutePath))
                ->post($url, [
                    'modality' => $medicalImage->modality,
                    'body_part' => $medicalImage->body_part,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Medical image AI service unavailable', [
                'message' => $exception->getMessage(),
            ]);

            return $this->markPending($medicalImage, 'Chưa kết nối được YOLO service. Ảnh đã được lưu và có thể phân tích lại sau.');
        }

        if (! $response->successful()) {
            Log::warning('Medical image AI service failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->markFailed($medicalImage, 'YOLO service trả lỗi khi phân tích ảnh.');
        }

        $payload = $response->json();

        $medicalImage->update([
            'analysis_status' => 'completed',
            'findings' => $payload['findings'] ?? $payload['detections'] ?? [],
            'summary' => $payload['summary'] ?? $this->summaryFromFindings($payload['findings'] ?? $payload['detections'] ?? []),
            'annotated_image_path' => $payload['annotated_image_path'] ?? null,
            'analyzed_at' => now(),
        ]);

        return $medicalImage->fresh();
    }

    private function analyzeWithGemini(MedicalImage $medicalImage): MedicalImage
    {
        if (! config('chatbot.gemini_api_key')) {
            return $this->markPending($medicalImage, 'Chưa cấu hình GEMINI_API_KEY. Ảnh đã được lưu và chờ bác sĩ đọc kết quả.');
        }

        $absolutePath = Storage::disk('public')->path($medicalImage->image_path);

        if (! is_file($absolutePath)) {
            return $this->markFailed($medicalImage, 'Không tìm thấy file ảnh đã upload.');
        }

        try {
            $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
            $imageData = base64_encode((string) file_get_contents($absolutePath));
            $model = config('chatbot.gemini_model', 'gemini-2.5-flash');
            $baseUrl = rtrim(config('chatbot.gemini_url'), '/');
            $url = "{$baseUrl}/{$model}:generateContent";

            $prompt = $this->patientImagePrompt($medicalImage);

            $response = Http::withHeaders([
                'x-goog-api-key' => config('chatbot.gemini_api_key'),
            ])
                ->timeout((int) config('medical_imaging.timeout', 45))
                ->retry(3, 1000, throw: false)
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mime,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 4096,
                        'thinkingConfig' => [
                            'thinkingBudget' => 0,
                        ],
                    ],
                ]);
        } catch (Throwable $exception) {
            Log::warning('Medical image Gemini service unavailable', [
                'message' => $exception->getMessage(),
            ]);

            return $this->markPending($medicalImage, 'Chưa kết nối được Gemini Vision. Ảnh đã được lưu và chờ bác sĩ đọc kết quả.');
        }

        if (! $response->successful()) {
            Log::warning('Medical image Gemini service failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (in_array($response->status(), [429, 503], true)) {
                return $this->markPending(
                    $medicalImage,
                    'Gemini Vision đang quá tải hoặc bị giới hạn tạm thời. Ảnh đã được lưu, vui lòng thử phân tích lại sau.'
                );
            }

            return $this->markFailed($medicalImage, $this->geminiErrorMessage($response->json()));
        }

        $payload = $response->json();
        $summary = $this->extractGeminiText($payload);
        $finishReason = $payload['candidates'][0]['finishReason'] ?? null;

        Log::info('Medical image Gemini response completed', [
            'medical_image_id' => $medicalImage->id,
            'finish_reason' => $finishReason,
            'usage' => $payload['usageMetadata'] ?? null,
        ]);

        if ($finishReason && ! in_array($finishReason, ['STOP', 'FINISH_REASON_UNSPECIFIED'], true)) {
            $summary = trim($summary)."\n\n[Lưu ý: Gemini kết thúc với trạng thái {$finishReason}, câu trả lời có thể chưa đầy đủ.]";
        }

        $medicalImage->update([
            'analysis_status' => 'completed',
            'findings' => [],
            'summary' => $summary ?: 'AI chưa đưa ra nhận xét rõ ràng. Bác sĩ cần đọc ảnh và xác nhận.',
            'analyzed_at' => now(),
        ]);

        return $medicalImage->fresh();
    }

    private function patientImagePrompt(MedicalImage $medicalImage): string
    {
        $modalityLabels = [
            'xray' => 'X-quang',
            'ct' => 'CT',
            'mri' => 'MRI',
            'ultrasound' => 'siêu âm',
            'endoscopy' => 'nội soi',
        ];

        $modality = $modalityLabels[$medicalImage->modality] ?? $medicalImage->modality;
        $bodyPart = $medicalImage->body_part ?: 'chưa rõ vùng chụp';
        $question = $medicalImage->note;

        return <<<TEXT
Bạn là trợ lý AI hỗ trợ bệnh nhân tìm hiểu ảnh y tế bằng tiếng Việt dễ hiểu.
Hãy xem ảnh {$modality}, vùng chụp: {$bodyPart}.

Câu hỏi của bệnh nhân:
{$question}

Yêu cầu trả lời:
- Trả lời trực tiếp câu hỏi của bệnh nhân ngay từ câu đầu tiên, không viết lời chào.
- Trả lời đầy đủ nhưng ngắn gọn trong khoảng 150-300 từ.
- Dựa trên nội dung nhìn thấy trong ảnh; nói rõ khi ảnh không cung cấp đủ thông tin để trả lời.
- Không khẳng định chẩn đoán chắc chắn.
- Không dùng từ ngữ gây hoảng sợ.
- Sau câu trả lời trực tiếp, nêu ngắn gọn các điểm AI quan sát được có liên quan.
- Nếu thấy bất thường nghi ngờ, giải thích bằng ngôn ngữ dễ hiểu.
- Nếu ảnh không đủ chất lượng hoặc không rõ, nói rõ cần bác sĩ/radiologist đọc phim.
- Kết thúc bằng câu: "Kết quả AI chỉ mang tính tham khảo, bạn cần bác sĩ xác nhận."
TEXT;
    }

    private function extractGeminiText(?array $body): string
    {
        $parts = $body['candidates'][0]['content']['parts'] ?? [];

        return collect($parts)
            ->pluck('text')
            ->filter()
            ->implode("\n");
    }

    private function markPending(MedicalImage $medicalImage, string $summary): MedicalImage
    {
        $medicalImage->update([
            'analysis_status' => 'pending',
            'summary' => $summary,
        ]);

        return $medicalImage->fresh();
    }

    private function markFailed(MedicalImage $medicalImage, string $summary): MedicalImage
    {
        $medicalImage->update([
            'analysis_status' => 'failed',
            'summary' => $summary,
        ]);

        return $medicalImage->fresh();
    }

    private function geminiErrorMessage(?array $payload): string
    {
        $status = $payload['error']['status'] ?? null;
        $message = $payload['error']['message'] ?? '';

        if (in_array($status, ['PERMISSION_DENIED', 'UNAUTHENTICATED'], true) || str_contains($message, 'API key')) {
            return 'Gemini Vision báo API key không hợp lệ hoặc chưa có quyền truy cập.';
        }

        if ($status === 'NOT_FOUND' || str_contains(strtolower($message), 'not found')) {
            return 'Gemini Vision báo model không tồn tại hoặc không hỗ trợ đọc ảnh.';
        }

        return 'Gemini Vision trả lỗi khi đọc ảnh. Bác sĩ sẽ cần đọc ảnh trực tiếp.';
    }

    private function summaryFromFindings(array $findings): string
    {
        if ($findings === []) {
            return 'AI chưa phát hiện vùng bất thường rõ ràng. Bác sĩ cần đọc phim và xác nhận.';
        }

        return 'AI phát hiện '.count($findings).' vùng nghi ngờ. Kết quả chỉ hỗ trợ sàng lọc, không thay thế bác sĩ.';
    }
}
