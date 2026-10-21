<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.admin_emails' => ['admin@gmail.com']]);
    }

    public function test_listed_and_verified_user_can_access_panel(): void
    {
        $user = User::factory()->make(['email' => 'admin@gmail.com', 'email_verified_at' => now()]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_unlisted_gmail_user_cannot_access_panel(): void
    {
        $user = User::factory()->make(['email' => 'someone@gmail.com', 'email_verified_at' => now()]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_listed_but_unverified_user_cannot_access_panel(): void
    {
        $user = User::factory()->make(['email' => 'admin@gmail.com', 'email_verified_at' => null]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_email_comparison_is_case_insensitive(): void
    {
        $user = User::factory()->make(['email' => 'Admin@Gmail.com', 'email_verified_at' => now()]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }
}
