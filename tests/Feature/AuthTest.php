<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_teacher_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-pass')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'nope')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
