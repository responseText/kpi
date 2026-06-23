<?php

namespace Tests\Feature;

use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use App\Models\KpiMain;
use App\Models\User;
use Database\Seeders\MouKpi2568Seeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * โครงสร้างใหม่: ยุทธศาสตร์ → กลยุทธ์ → หมวด KPI → KPI หลัก → ตัวชี้วัด
 * + การนำเข้าข้อมูลจากเอกสาร MOU 2568
 */
class KpiHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    private function plainUser(): User
    {
        return User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
    }

    public function test_category_and_main_menus_restricted_to_indicator_managers(): void
    {
        // ผู้ใช้ทั่วไป (ไม่มีบทบาทผู้ดูแล) → เข้าเมนูหมวด KPI / KPI หลัก ไม่ได้
        $plain = $this->plainUser();
        $this->actingAs($plain->fresh())->get('/categories')->assertForbidden();
        $this->actingAs($plain->fresh())->get('/mains')->assertForbidden();

        // ผู้ดูแลระบบสูงสุด → เข้าได้
        $this->actingAs($this->admin())->get('/categories')->assertOk();
        $this->actingAs($this->admin())->get('/mains')->assertOk();
    }

    public function test_super_admin_can_create_category_main_and_indicator_under_main(): void
    {
        $admin = $this->admin();

        // หมวด KPI
        $this->actingAs($admin)->post('/categories', [
            'name' => 'HIER หมวด KPI ทดสอบ', 'status' => 'enable',
        ])->assertRedirect('/categories');
        $category = KpiCategory::where('name', 'HIER หมวด KPI ทดสอบ')->firstOrFail();

        // KPI หลัก ใต้หมวด
        $this->actingAs($admin)->post('/mains', [
            'category_id' => $category->id, 'name' => 'HIER KPI หลัก ทดสอบ', 'status' => 'enable',
        ])->assertRedirect('/mains');
        $main = KpiMain::where('name', 'HIER KPI หลัก ทดสอบ')->firstOrFail();
        $this->assertEquals($category->id, $main->category_id);

        // ตัวชี้วัด ใต้ KPI หลัก
        $this->actingAs($admin)->post('/indicators', [
            'kpi_main_id' => $main->id, 'level' => 'hospital', 'name' => 'HIER ตัวชี้วัดทดสอบ',
            'year_type' => 'fiscal', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
            'owners' => [$admin->id], 'primary_owner' => $admin->id,
        ])->assertRedirect();
        $indicator = KpiIndicator::where('name', 'HIER ตัวชี้วัดทดสอบ')->firstOrFail();
        $this->assertEquals($main->id, $indicator->kpi_main_id);
    }

    public function test_mou_seeder_imports_full_hierarchy_and_clears_demo_indicators(): void
    {
        $this->seed(MouKpi2568Seeder::class);

        $this->assertSame(17, KpiCategory::count());
        $this->assertSame(26, KpiMain::count());
        $this->assertSame(51, KpiIndicator::count());

        // ตัวชี้วัดทุกตัวผูกกับ KPI หลัก และไม่มีตัวที่ค้างไม่มี KPI หลัก (ตัวเดโมถูกล้างแล้ว)
        $this->assertSame(0, KpiIndicator::whereNull('kpi_main_id')->count());

        // หมวด KPI ที่นำเข้ายังไม่ผูกกับกลยุทธ์
        $this->assertSame(17, KpiCategory::whereNull('sub_strategy_id')->count());
    }
}
