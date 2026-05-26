<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    public function test_guest_cannot_access_home_page(): void
    {
        $response = $this->get('/trang-chu');

        $response->assertRedirect(route('login'));
    }

    public function test_register_page_returns_a_successful_response(): void
    {
        $response = $this->get('/dang-ky');

        $response->assertStatus(200);
        $response->assertSee('Đăng ký và đăng nhập');
    }
}
