<?php

namespace Tests\Feature\Api\AutoX;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticated(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('mobile')->plainTextToken);
    }

    public function test_a_user_can_view_their_own_profile()
    {
        $user = User::factory()->create(['first_name' => 'Kojo']);

        $response = $this->authenticated($user)->getJson('/api/auto-x/profile');

        $response->assertOk();
        $response->assertJsonPath('data.first_name', 'Kojo');
    }

    public function test_a_user_can_update_their_name_and_it_clears_verification_on_email_change()
    {
        $user = User::factory()->create(['email' => 'old@example.test', 'email_verified_at' => now()]);

        $response = $this->authenticated($user)->patchJson('/api/auto-x/profile', [
            'first_name' => 'NewFirst',
            'email' => 'new@example.test',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.first_name', 'NewFirst');
        $response->assertJsonPath('data.email_verified_at', null);
        $this->assertSame('new@example.test', $user->refresh()->email);
    }

    public function test_a_user_can_change_their_password_with_the_correct_current_password()
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->authenticated($user)->putJson('/api/auto-x/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertNoContent();
        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_changing_password_requires_the_correct_current_password()
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->authenticated($user)->putJson('/api/auto-x/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertUnprocessable();
    }
}
