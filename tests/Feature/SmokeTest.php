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
            // กราฟแนวโน้มอัตราผ่านรวมรายปี (sparkline ในแผงเมตริกของ hero)
            ->assertSee('แนวโน้มรายปี')
            ->assertSee('sparkFill');
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

    public function test_guest_can_access_public_dashboard_and_monitor(): void
    {
        // แดชบอร์ด/Monitor เปิดสาธารณะ — ผู้ที่ยังไม่ล็อกอินเข้าดูได้ทุกระดับ
        $this->get('/dashboard')->assertOk()->assertSee('แดชบอร์ดตัวชี้วัด');
        $this->get(route('dashboard.ministry'))->assertOk();
        $this->get(route('dashboard.province'))->assertOk();
        $this->get(route('dashboard.hospital'))->assertOk();
        $this->get('/monitor')->assertOk();
    }

    public function test_guest_dashboard_shows_login_button(): void
    {
        // ผู้เยี่ยมชมต้องเห็นปุ่ม "เข้าสู่ระบบ" ที่ลิงก์ไปหน้าล็อกอิน
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('เข้าสู่ระบบ')
            ->assertSee(route('login'), false);
    }

    public function test_protected_route_still_redirects_guest_to_login(): void
    {
        // หน้าอื่น ๆ ที่ต้องล็อกอินยังคงเด้งไปหน้าล็อกอินเหมือนเดิม
        $this->get('/strategies')->assertRedirect('/login');
    }
}
