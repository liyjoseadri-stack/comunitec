<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_user_has_the_administrator_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->isAdministrator());
        $this->assertFalse($user->isCommercial());
        $this->assertFalse($user->isConsultation());
    }
}
