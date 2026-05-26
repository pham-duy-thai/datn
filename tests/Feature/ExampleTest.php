<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_entry_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/dang-nhap');

        $response->assertStatus(200);
        $response->assertSee('Đăng nhập');
    }
}
