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
            ->assertSee('แดชบอร์ดตัวชี้วัด')
            // กราฟเส้นพื้นหลัง hero (แนวโน้มอัตราผ่านรวมย้อนหลังรายปี)
            ->assertSee('heroTrendFill');
    }

    public function test_level_dashboard_renders_premium_stat_cards(): void
    {
        // หน้าแดชบอร์ดรายระดับ (เลือกระดับเดียว) ต้องแสดงการ์ดสถิติเด่นชุด 4 ใบ
        $this->actingAs($this->admin())
            ->get(route('dashboard.hospital'))
            ->assertOk()
            ->assertSee('ตัวชี้วัดทั้งหมด')
            ->assertSee('ผ่านเกณฑ์')
            ->assertSee('รอบันทึกผล');
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
