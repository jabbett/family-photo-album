<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_email_with_valid_verification_url(): void
    {
        Event::fake();
        
        $user = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirect(route('home') . '?verified=1');
        
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_user_with_already_verified_email_gets_redirected(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirect(route('home') . '?verified=1');
        
        // Should still be verified
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_email_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_email_with_expired_url(): void
    {
        $user = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10), // Expired URL
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unauthenticated_user_cannot_verify_email(): void
    {
        $user = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/login');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_another_users_email(): void
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user1->id, 'hash' => sha1($user1->email)]
        );

        // User2 tries to verify User1's email
        $response = $this->actingAs($user2)
            ->get($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user1->fresh()->hasVerifiedEmail());
        $this->assertFalse($user2->fresh()->hasVerifiedEmail());
    }
}