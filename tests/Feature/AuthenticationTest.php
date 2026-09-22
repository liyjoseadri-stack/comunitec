<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_dashboard(): void
    {
        $this->get('/panel')->assertRedirect('/iniciar-sesion');
    }

    public function test_active_user_can_sign_in_and_see_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
        ]);

        $this->post('/iniciar-sesion', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($user);
        $this->get('/panel')->assertOk()->assertSee('Panel principal');
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'active' => false,
            'password' => Hash::make('secret-password'),
        ]);

        $this->from('/iniciar-sesion')->post('/iniciar-sesion', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/iniciar-sesion')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
