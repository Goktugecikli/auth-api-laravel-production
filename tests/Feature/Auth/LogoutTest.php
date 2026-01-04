<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $this->withoutMiddleware(
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class
        );

        $user = User::factory()->create();

        $tokenResult = $user->createToken('api', ['profile:read', 'auth:logout']);
        $plainToken  = $tokenResult->plainTextToken;
        $tokenId     = $tokenResult->accessToken->id;

        $this->withToken($plainToken)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);

        // ✅ kritik: auth state temizle (bir önceki request'in user cache'i kalmasın)
        $this->app['auth']->forgetGuards();

        $this->withToken($plainToken)->getJson('/api/auth/me')->assertStatus(401);
    }

}
