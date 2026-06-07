<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for SQLite memory database tests.');
        }

        parent::setUp();
    }

    public function test_web_register_creates_patient_user_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0900000001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0900000001',
            'role' => 'patient',
        ]);
    }

    public function test_admin_create_user_creates_login_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Tran Thi B',
            'email' => 'tranthib@example.com',
            'phone' => '0900000002',
            'role' => 'receptionist',
            'gender' => 'female',
            'date_of_birth' => '1995-01-02',
            'address' => 'Ha Noi',
            'avatar' => null,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'tranthib@example.com')->firstOrFail();
        $this->assertSame('receptionist', $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_admin_can_reset_user_password_to_default_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);
        $user = User::factory()->create([
            'role' => 'patient',
            'email' => 'patient@example.com',
            'password' => Hash::make('old-password'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.reset-password', $user));

        $response->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue($user->must_change_password);
    }
}
