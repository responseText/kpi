<?php

namespace Tests\Feature;

use App\Models\KpiLevel;
use App\Models\KpiUnit;
use App\Models\User;
use App\Models\UserOnLevel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * จัดการหน่วยวัด KPI (master) — เฉพาะผู้ดูแลระบบสูงสุด + การเชื่อมกับฟอร์มตัวชี้วัด
 */
class KpiUnitTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    public function test_unit_menu_restricted_to_super_admin(): void
    {
        // ผู้ดูแลระบบสูงสุด → เข้าได้
        $this->actingAs($this->admin())->get('/units')->assertOk();

        // ผู้ดูแลตัวชี้วัดทั้งหมด (ไม่ใช่ super admin) → ถูกปฏิเสธ (403) เพราะเมนูนี้เฉพาะผู้ดูแลระบบสูงสุด
        $allId = KpiLevel::where('code', KpiLevel::ADMIN_ALL)->value('id');
        $adminAll = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        UserOnLevel::create(['user_id' => $adminAll->id, 'alias_system' => 'kpi', 'level_id' => $allId, 'is_super_admin' => false]);
        $this->actingAs($adminAll->fresh())->get('/units')->assertForbidden();

        // ผู้ใช้ทั่วไป → ถูกปฏิเสธ (403)
        $plain = User::whereNotIn('id', [1, 2, $adminAll->id])->orderBy('id')->firstOrFail();
        $this->actingAs($plain->fresh())->get('/units')->assertForbidden();
    }

    public function test_super_admin_can_crud_units_and_name_is_unique(): void
    {
        $admin = $this->admin();

        // สร้าง
        $this->actingAs($admin)->post('/units', [
            'group_code' => KpiUnit::GROUP_QUANTITY,
            'name' => 'หน่วยทดสอบUNITMARK',
            'status' => 'enable',
        ])->assertRedirect('/units');
        $this->assertDatabaseHas('kpi_units', ['name' => 'หน่วยทดสอบUNITMARK', 'group_code' => 'quantity']);
        $unit = KpiUnit::where('name', 'หน่วยทดสอบUNITMARK')->firstOrFail();

        // ชื่อซ้ำ (แม้คนละกลุ่ม) → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/units', [
            'group_code' => KpiUnit::GROUP_QUALITY,
            'name' => 'หน่วยทดสอบUNITMARK',
            'status' => 'enable',
        ])->assertSessionHasErrors('name');

        // กลุ่ม KPI ไม่ถูกต้อง → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/units', [
            'group_code' => 'invalid_group',
            'name' => 'หน่วยทดสอบUNITMARK2',
            'status' => 'enable',
        ])->assertSessionHasErrors('group_code');

        // แก้ไข (ย้ายกลุ่ม + ปิดใช้งาน)
        $this->actingAs($admin)->put("/units/{$unit->id}", [
            'group_code' => KpiUnit::GROUP_EFFICIENCY,
            'name' => 'หน่วยทดสอบUNITMARK',
            'status' => 'disable',
        ])->assertRedirect('/units');
        $this->assertDatabaseHas('kpi_units', ['id' => $unit->id, 'group_code' => 'efficiency', 'status' => 'disable']);

        // ลบแบบ soft delete — แถวยังอยู่ในฐานข้อมูล (deleted_at ถูกตั้งค่า) แต่ไม่แสดงในรายการ
        $this->actingAs($admin)->delete("/units/{$unit->id}")->assertRedirect('/units');
        $this->assertSoftDeleted('kpi_units', ['id' => $unit->id]);
        $this->actingAs($admin)->get('/units')->assertOk()->assertDontSee('หน่วยทดสอบUNITMARK');

        // ชื่อที่เคยถูกลบ (soft delete) นำกลับมาสร้างใหม่ได้
        $this->actingAs($admin)->post('/units', [
            'group_code' => KpiUnit::GROUP_QUALITY,
            'name' => 'หน่วยทดสอบUNITMARK',
            'status' => 'enable',
        ])->assertRedirect('/units');
        $this->assertDatabaseHas('kpi_units', [
            'name' => 'หน่วยทดสอบUNITMARK', 'group_code' => 'quality', 'deleted_at' => null,
        ]);
    }

    public function test_enabled_units_appear_grouped_in_indicator_form(): void
    {
        $admin = $this->admin();

        KpiUnit::create(['group_code' => KpiUnit::GROUP_QUANTITY, 'name' => 'เตียงUNITFORM', 'status' => 'enable']);
        KpiUnit::create(['group_code' => KpiUnit::GROUP_QUANTITY, 'name' => 'ปิดUNITFORM', 'status' => 'disable']);

        $res = $this->actingAs($admin)->get('/indicators/create');
        $res->assertOk();
        $res->assertSee('เตียงUNITFORM');            // หน่วยที่เปิดใช้งานปรากฏใน dropdown
        $res->assertSee('เชิงปริมาณ (Quantity)');     // ป้ายกลุ่ม (optgroup)
        $res->assertDontSee('ปิดUNITFORM');            // หน่วยที่ปิดใช้งานไม่ปรากฏ
    }
}
