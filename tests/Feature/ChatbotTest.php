<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatbotTest extends TestCase
{
    public function test_guest_can_receive_chatbot_reply(): void
    {
        $response = $this->postJson('/tro-ly-ai', [
            'message' => 'Tôi muốn đặt lịch khám',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.source', 'fallback')
            ->assertJsonFragment([
                'answer' => 'Bạn có thể vào mục Đặt lịch, chọn bác sĩ, dịch vụ, ngày giờ khám và gửi thông tin người khám. Sau khi gửi, lịch hẹn sẽ ở trạng thái chờ xác nhận để bệnh viện xử lý.',
            ]);
    }
}
