<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_and_get_token(): void
    {
        $password = 'password123';
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['user', 'token'],
            ]);
    }

    public function test_cannot_access_profile_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertStatus(401);
    }
}
