<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    private function admin(): User
    {
        // ผู้ดูแลระบบเริ่มต้น (มีสิทธิ์เต็มจาก PermissionSeeder)
        return User::findOrFail(1);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('เข้าสู่ระบบ');
    }

    public function test_dashboard_renders_for_authed_user(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('แดชบอร์ดตัวชี้วัด');
    }

    public function test_monitor_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/monitor')
            ->assertOk()
            ->assertSee('ตัวชี้วัดผลงาน');
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
