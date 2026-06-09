<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MedicalAiService
{
    public function assist(array $data, User $doctorUser, ?MedicalRecord $record = null): array
    {
        $provider = config('chatbot.ai_provider', 'openai');

        if ($provider === 'gemini') {
            return $this->assistWithGemini($data, $doctorUser, $record);
        }

        return $this->assistWithOpenAi($data, $doctorUser, $record);
    }

    private function assistWithOpenAi(array $data, User $doctorUser, ?MedicalRecord $record): array
    {
        if (! config('chatbot.openai_api_key')) {
            return [
                'answer' => 'Chưa cấu hình OPENAI_API_KEY nên không thể gọi ChatGPT API. Vui lòng cấu hình khóa API trong .env để sử dụng AI hỗ trợ bác sĩ.',
                'source' => 'openai_unconfigured',
            ];
        }

        try {
            $answer = $this->replyWithOpenAi($data, $doctorUser, $record);
        } catch (Throwable $exception) {
            Log::warning('Medical AI OpenAI request failed', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'answer' => $this->openAiErrorMessage($exception),
                'source' => 'openai_error',
            ];
        }

        return [
            'answer' => $answer ?: 'ChatGPT API chưa trả về nội dung phù hợp. Bác sĩ vui lòng thử lại với dữ liệu lâm sàng rõ hơn.',
            'source' => 'openai',
        ];
    }

    private function assistWithGemini(array $data, User $doctorUser, ?MedicalRecord $record): array
    {
        if (! config('chatbot.gemini_api_key')) {
            return [
                'answer' => 'Chưa cấu hình GEMINI_API_KEY nên không thể gọi Gemini API. Vui lòng cấu hình khóa API trong .env để sử dụng AI hỗ trợ bác sĩ.',
                'source' => 'gemini_unconfigured',
            ];
        }

        try {
            $answer = $this->replyWithGemini($data, $doctorUser, $record);
        } catch (Throwable $exception) {
            Log::warning('Medical AI Gemini request failed', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'answer' => $this->geminiErrorMessage($exception),
                'source' => 'gemini_error',
            ];
        }

        return [
            'answer' => $answer ?: 'Gemini API chưa trả về nội dung phù hợp. Bác sĩ vui lòng thử lại với dữ liệu lâm sàng rõ hơn.',
            'source' => 'gemini',
        ];
    }

    private function replyWithOpenAi(array $data, User $doctorUser, ?MedicalRecord $record): ?string
    {
        $mode = $this->modeLabel($data['mode']);
        $context = $this->contextText($data, $record);
        $doctorProfile = $doctorUser->relationLoaded('doctor') ? $doctorUser->doctor : null;
        $doctorName = $doctorProfile?->name ?: $doctorUser->name;

        $instructions = <<<TEXT
Bạn là trợ lý AI hỗ trợ bác sĩ trong hệ thống bệnh viện.
Trả lời bằng tiếng Việt, rõ ràng, có cấu trúc, ngắn gọn nhưng đủ ý.
Chỉ đưa thông tin hỗ trợ tham khảo cho bác sĩ, không khẳng định chẩn đoán cuối cùng.
Luôn nhắc rằng bác sĩ là người quyết định cuối cùng dựa trên thăm khám trực tiếp và dữ liệu đầy đủ.
Nếu dữ liệu thiếu, hãy nêu rõ dữ liệu cần bổ sung. Nếu có dấu hiệu cấp cứu hoặc nguy cơ cao, ưu tiên cảnh báo xử trí khẩn.
Không bịa kết quả xét nghiệm, thuốc, dị ứng, thai kỳ, bệnh nền hoặc tiền sử nếu chưa được cung cấp.
Riêng yêu cầu gợi ý chẩn đoán: hãy liệt kê một số khả năng bệnh/chẩn đoán phân biệt có thể nghĩ tới dựa trên dữ liệu được cung cấp, mỗi khả năng kèm lý do ủng hộ, dữ liệu chống lại hoặc còn thiếu, và đề xuất bước kiểm tra/xét nghiệm tiếp theo. Không ghi "chẩn đoán xác định" nếu dữ liệu chưa đủ.
TEXT;

        $input = <<<TEXT
Bác sĩ: {$doctorName}
Yêu cầu hỗ trợ: {$mode}

Dữ liệu lâm sàng:
{$context}
TEXT;

        $response = Http::withToken(config('chatbot.openai_api_key'))
            ->timeout(30)
            ->post(config('chatbot.openai_url'), [
                'model' => config('chatbot.openai_model'),
                'instructions' => $instructions,
                'input' => $input,
                'max_output_tokens' => 900,
            ]);

        if (! $response->successful()) {
            Log::warning('Medical AI OpenAI request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($response->body());
        }

        return $this->extractOpenAiText($response->json());
    }

    private function replyWithGemini(array $data, User $doctorUser, ?MedicalRecord $record): ?string
    {
        $prompt = $this->promptText($data, $doctorUser, $record);
        $model = config('chatbot.gemini_model');
        $baseUrl = rtrim(config('chatbot.gemini_url'), '/');
        $url = "{$baseUrl}/{$model}:generateContent";

        $response = Http::withHeaders([
            'x-goog-api-key' => config('chatbot.gemini_api_key'),
        ])
            ->timeout(30)
            ->retry(3, 1000, throw: false)
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
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

        if (! $response->successful()) {
            Log::warning('Medical AI Gemini request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($response->body());
        }

        return $this->extractGeminiText($response->json());
    }

    private function promptText(array $data, User $doctorUser, ?MedicalRecord $record): string
    {
        $mode = $this->modeLabel($data['mode']);
        $context = $this->contextText($data, $record);
        $doctorProfile = $doctorUser->relationLoaded('doctor') ? $doctorUser->doctor : null;
        $doctorName = $doctorProfile?->name ?: $doctorUser->name;

        return <<<TEXT
Không viết lời chào. Bạn là trợ lý AI hỗ trợ bác sĩ trong hệ thống bệnh viện.
Trả lời bằng tiếng Việt, rõ ràng, có cấu trúc, ngắn gọn dưới 600 từ và phải hoàn thành trọn vẹn câu cuối.
AI chỉ đưa gợi ý tham khảo, không khẳng định chẩn đoán cuối cùng và không thay thế bác sĩ.
Không bịa dữ liệu chưa được cung cấp.

Bác sĩ: {$doctorName}
Yêu cầu hỗ trợ: {$mode}

Dữ liệu lâm sàng:
{$context}

Yêu cầu định dạng:
- Nếu là gợi ý chẩn đoán: liệt kê đúng 3 khả năng bệnh/chẩn đoán phân biệt có thể nghĩ tới dựa trên dữ liệu đã nhập. Mỗi mục chỉ gồm: tên bệnh, 1 dòng lý do gợi ý, 1 dòng dữ liệu còn thiếu/cần kiểm tra thêm. Nếu dữ liệu rất ít, vẫn nêu các khả năng thường gặp và ghi rõ cần bổ sung gì.
- Nếu là tóm tắt bệnh án: tóm tắt lý do vào viện, bệnh sử, tiền sử/dị ứng/thuốc, kết quả bất thường, các lần khám trước nếu có.
- Nếu là hỗ trợ kê đơn: cảnh báo dị ứng thuốc, tương tác, trùng hoạt chất, liều bất thường và chống chỉ định theo tuổi/thai kỳ/bệnh nền nếu có dữ liệu.
- Nếu là viết bệnh án: soạn nội dung có cấu trúc gồm triệu chứng hiện tại, chẩn đoán sơ bộ, chỉ định cận lâm sàng, hướng điều trị, lời dặn.

Kết thúc bằng câu: "Bác sĩ là người quyết định cuối cùng."
TEXT;
    }

    private function openAiErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $decoded = json_decode($message, true);
        $code = $decoded['error']['code'] ?? null;
        $type = $decoded['error']['type'] ?? null;

        if ($code === 'insufficient_quota' || $type === 'insufficient_quota' || str_contains($message, 'insufficient_quota')) {
            return 'ChatGPT API báo hết quota hoặc project chưa có billing. Vui lòng kiểm tra Usage/Billing trong OpenAI Dashboard, nạp credits hoặc dùng API key thuộc project còn quota.';
        }

        if (str_contains($message, 'invalid_api_key') || str_contains($message, 'Incorrect API key')) {
            return 'ChatGPT API báo API key không hợp lệ. Vui lòng kiểm tra lại OPENAI_API_KEY trong .env.';
        }

        return 'Không gọi được ChatGPT API. Vui lòng kiểm tra OPENAI_API_KEY, model, endpoint và kết nối mạng.';
    }

    private function geminiErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $decoded = json_decode($message, true);
        $status = $decoded['error']['status'] ?? null;
        $errorMessage = $decoded['error']['message'] ?? $message;

        if (in_array($status, ['PERMISSION_DENIED', 'UNAUTHENTICATED'], true) || str_contains($errorMessage, 'API key')) {
            return 'Gemini API báo khóa API không hợp lệ hoặc chưa có quyền. Vui lòng kiểm tra GEMINI_API_KEY và project Google AI Studio.';
        }

        if ($status === 'RESOURCE_EXHAUSTED' || str_contains($errorMessage, 'quota')) {
            return 'Gemini API báo hết quota hoặc bị giới hạn tốc độ. Vui lòng kiểm tra quota/billing của project Google AI Studio.';
        }

        if ($status === 'NOT_FOUND' || str_contains($errorMessage, 'not found')) {
            return 'Gemini API báo model không tồn tại hoặc không hỗ trợ generateContent. Vui lòng kiểm tra GEMINI_MODEL trong .env.';
        }

        return 'Không gọi được Gemini API. Vui lòng kiểm tra GEMINI_API_KEY, model, endpoint và kết nối mạng.';
    }

    private function contextText(array $data, ?MedicalRecord $record): string
    {
        $lines = [
            'Triệu chứng hiện tại' => $data['symptoms'] ?? null,
            'Tiền sử bệnh' => $data['medical_history'] ?? null,
            'Kết quả xét nghiệm' => $data['lab_results'] ?? null,
            'Chỉ số sinh tồn' => $data['vital_signs'] ?? null,
            'Dị ứng thuốc' => $data['allergies'] ?? null,
            'Thuốc đang dùng/đơn thuốc cần kiểm tra' => $data['current_medications'] ?? null,
            'Ghi chú thêm của bác sĩ' => $data['note'] ?? null,
        ];

        if ($record) {
            $lines += [
                'Hồ sơ đã chọn - bệnh nhân' => $record->user?->name ?? $record->appointment?->patient_name,
                'Hồ sơ đã chọn - ngày khám' => $record->examined_at?->format('d/m/Y'),
                'Hồ sơ đã chọn - triệu chứng' => $record->symptoms,
                'Hồ sơ đã chọn - chẩn đoán' => $record->diagnosis,
                'Hồ sơ đã chọn - điều trị' => $record->treatment,
                'Hồ sơ đã chọn - đơn thuốc' => $record->prescription,
                'Hồ sơ đã chọn - ghi chú' => $record->note,
            ];
        }

        return collect($lines)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $label) => "{$label}: {$value}")
            ->implode("\n");
    }

    private function modeLabel(string $mode): string
    {
        return [
            'diagnosis' => 'Gợi ý khả năng bệnh/chẩn đoán phân biệt',
            'summary' => 'Tóm tắt bệnh án',
            'prescription' => 'Cảnh báo hỗ trợ kê đơn',
            'record_draft' => 'Soạn bệnh án có cấu trúc',
        ][$mode] ?? 'Hỗ trợ lâm sàng';
    }

    private function extractOpenAiText(?array $body): ?string
    {
        if (! empty($body['output_text']) && is_string($body['output_text'])) {
            return trim($body['output_text']);
        }

        foreach ($body['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && ! empty($content['text'])) {
                    return trim($content['text']);
                }
            }
        }

        return null;
    }

    private function extractGeminiText(?array $body): ?string
    {
        $parts = $body['candidates'][0]['content']['parts'] ?? [];

        $text = collect($parts)
            ->pluck('text')
            ->filter()
            ->implode("\n");

        $finishReason = $body['candidates'][0]['finishReason'] ?? null;

        if ($finishReason && ! in_array($finishReason, ['STOP', 'FINISH_REASON_UNSPECIFIED'], true)) {
            $text = trim($text)."\n\n[Lưu ý: Gemini kết thúc với trạng thái {$finishReason}, nội dung có thể chưa đầy đủ.]";
        }

        return $text;
    }
}
