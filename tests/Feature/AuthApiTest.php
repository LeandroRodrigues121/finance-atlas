<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => '123456',
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'admin',
            'password' => '123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'username', 'email'],
            ]);
    }
}
