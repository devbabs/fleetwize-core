<?php

namespace Tests\Feature\Api\AutoX;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_token()
    {
        $response = $this->postJson('/api/auto-x/auth/register', [
            'first_name' => 'Kojo',
            'last_name' => 'Customer',
            'email' => 'kojo@example.test',
            'phone' => '0551234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.first_name', 'Kojo');
        $response->assertJsonPath('user.email', 'kojo@example.test');
        $this->assertIsString($response->json('token'));
        $this->assertDatabaseHas('users', ['email' => 'kojo@example.test']);
    }

    public function test_registration_rejects_a_duplicate_email()
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $response = $this->postJson('/api/auto-x/auth/register', [
            'first_name' => 'Kojo',
            'last_name' => 'Customer',
            'email' => 'taken@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_a_user_can_log_in_with_correct_credentials()
    {
        User::factory()->create([
            'email' => 'kojo@example.test',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auto-x/auth/login', [
            'email' => 'kojo@example.test',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.email', 'kojo@example.test');
        $this->assertIsString($response->json('token'));
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'kojo@example.test',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auto-x/auth/login', [
            'email' => 'kojo@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_a_token_can_be_revoked_via_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auto-x/auth/logout');

        $response->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_routes_require_a_token()
    {
        $response = $this->getJson('/api/auto-x/vehicles');

        $response->assertUnauthorized();
    }
}
