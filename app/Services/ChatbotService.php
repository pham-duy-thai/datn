<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotService
{
    public function reply(string $message, ?User $user = null): array
    {
        $message = trim($message);

        if ($message === '') {
            return [
                'answer' => 'Bạn vui lòng nhập câu hỏi để trợ lý có thể hỗ trợ.',
                'source' => 'fallback',
            ];
        }

        if (config('chatbot.openai_api_key')) {
            try {
                $answer = $this->replyWithOpenAi($message, $user);

                if ($answer !== null) {
                    return [
                        'answer' => $answer,
                        'source' => 'openai',
                    ];
                }
            } catch (Throwable $exception) {
                Log::warning('AI chatbot fallback', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'answer' => $this->fallbackReply($message),
            'source' => 'fallback',
        ];
    }

    private function replyWithOpenAi(string $message, ?User $user): ?string
    {
        $context = $this->hospitalContext();
        $userName = $user?->name ?: 'khách hàng';
        $instructions = <<<TEXT
Bạn là trợ lý chăm sóc khách hàng của {$context['hospital_name']}.
Trả lời bằng tiếng Việt, thân thiện, ngắn gọn và đúng dữ liệu bệnh viện.
Chỉ tư vấn thông tin đặt lịch, chuyên khoa, bác sĩ, dịch vụ, giá dịch vụ, liên hệ và quy trình khám.
Không chẩn đoán bệnh, không kê đơn, không thay thế bác sĩ. Với dấu hiệu cấp cứu, khuyên gọi {$context['hotline']} hoặc đến cơ sở y tế gần nhất.

Dữ liệu bệnh viện:
Hotline: {$context['hotline']}
Email: {$context['support_email']}
Chuyên khoa: {$context['departments']}
Dịch vụ: {$context['services']}
Bác sĩ: {$context['doctors']}
TEXT;

        $response = Http::withToken(config('chatbot.openai_api_key'))
            ->timeout(20)
            ->post(config('chatbot.openai_url'), [
                'model' => config('chatbot.openai_model'),
                'instructions' => $instructions,
                'input' => "Khách hàng {$userName} hỏi: {$message}",
                'max_output_tokens' => 450,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($response->body());
        }

        $body = $response->json();

        return $this->extractOpenAiText($body);
    }

    private function fallbackReply(string $message): string
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $hotline = config('chatbot.hotline');
        $supportEmail = config('chatbot.support_email');

        if ($this->contains($normalized, ['cấp cứu', 'cap cuu', 'khó thở', 'kho tho', 'đau ngực', 'dau nguc', 'ngất', 'ngat'])) {
            return "Nếu có dấu hiệu cấp cứu như khó thở, đau ngực, ngất hoặc chảy máu nhiều, bạn nên gọi {$hotline} hoặc đến cơ sở y tế gần nhất ngay. Trợ lý không thay thế bác sĩ trong tình huống khẩn cấp.";
        }

        if ($this->contains($normalized, ['đặt lịch', 'dat lich', 'lịch khám', 'lich kham', 'khám bệnh', 'kham benh'])) {
            return 'Bạn có thể vào mục Đặt lịch, chọn bác sĩ, dịch vụ, ngày giờ khám và gửi thông tin người khám. Sau khi gửi, lịch hẹn sẽ ở trạng thái chờ xác nhận để bệnh viện xử lý.';
        }

        if ($this->contains($normalized, ['chuyên khoa', 'chuyen khoa', 'khoa nào', 'khoa nao'])) {
            $context = $this->hospitalContext();

            return "Các chuyên khoa hiện có: {$context['departments']}. Bạn có thể vào mục Chuyên khoa để xem chi tiết và chọn bác sĩ phù hợp.";
        }

        if ($this->contains($normalized, ['bác sĩ', 'bac si', 'doctor'])) {
            $context = $this->hospitalContext();

            return "Một số bác sĩ đang có lịch khám: {$context['doctors']}. Bạn có thể vào mục Bác sĩ để xem hồ sơ và đặt lịch.";
        }

        if ($this->contains($normalized, ['dịch vụ', 'dich vu', 'giá', 'gia', 'phí', 'phi', 'bao nhiêu', 'bao nhieu'])) {
            $context = $this->hospitalContext();

            return "Một số dịch vụ và phí tham khảo: {$context['services']}. Giá có thể thay đổi theo chỉ định thực tế khi khám.";
        }

        if ($this->contains($normalized, ['liên hệ', 'lien he', 'số điện thoại', 'so dien thoai', 'hotline', 'email'])) {
            return "Bạn có thể liên hệ bệnh viện qua hotline {$hotline} hoặc email {$supportEmail}.";
        }

        if ($this->contains($normalized, ['thanh toán', 'thanh toan', 'momo', 'vnpay', 'tiền mặt', 'tien mat', 'đặt cọc', 'dat coc'])) {
            return 'Hiện hệ thống hỗ trợ thanh toán bằng tiền mặt tại bệnh viện, VNPay sandbox và MoMo sandbox. Khi thanh toán thành công, lịch hẹn sẽ chuyển sang trạng thái đã xác nhận.';
        }

        return 'Tôi có thể hỗ trợ về đặt lịch khám, chuyên khoa, bác sĩ, dịch vụ, phí khám và thông tin liên hệ. Bạn muốn hỏi nội dung nào?';
    }

    private function hospitalContext(): array
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(8)
            ->pluck('name')
            ->implode(', ');

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Service $service) => $service->name.' - '.number_format((float) $service->price, 0, ',', '.').' VNĐ')
            ->implode('; ');

        $doctors = Doctor::query()
            ->with('department')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Doctor $doctor) => $doctor->name.' ('.($doctor->department?->name ?? 'chưa gắn khoa').')')
            ->implode('; ');

        return [
            'hospital_name' => config('chatbot.hospital_name'),
            'hotline' => config('chatbot.hotline'),
            'support_email' => config('chatbot.support_email'),
            'departments' => $departments ?: 'chưa có dữ liệu chuyên khoa',
            'services' => $services ?: 'chưa có dữ liệu dịch vụ',
            'doctors' => $doctors ?: 'chưa có dữ liệu bác sĩ',
        ];
    }

    private function contains(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
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
}
