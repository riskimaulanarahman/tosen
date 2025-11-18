<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Debug: Check the verification URL
        $request = Request::create($verificationUrl);
        $this->assertTrue(URL::hasValidSignature($request), 'Verification URL should have valid signature');

        // Ensure user is authenticated
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->get($verificationUrl);

        // Debug: Check response status and content
        $response->dump();
        
        // Debug: Check if user was actually verified
        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->hasVerifiedEmail(), 'User email should be verified');
        
        // Debug: Check response status
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        
        Event::assertDispatched(Verified::class);
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
