<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiResult;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * ทดสอบ flow หลักแบบ end-to-end (ห่อด้วย transaction → rollback อัตโนมัติ ไม่ทิ้งข้อมูลใน coretsk)
 */
class KpiFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    public function test_full_kpi_lifecycle(): void
    {
        $admin = $this->admin();
        $reviewerId = User::where('id', '!=', 1)->value('id');

        // 1) สร้างยุทธศาสตร์
        $this->actingAs($admin)->post('/strategies', [
            'year' => 2569, 'code' => 'ย.1', 'name' => 'ยุทธศาสตร์ทดสอบ', 'status' => 'enable',
        ])->assertRedirect('/strategies');
        $strategy = \App\Models\KpiStrategy::where('name', 'ยุทธศาสตร์ทดสอบ')->firstOrFail();

        // 2) สร้างกลยุทธ์ + ผู้ตรวจสอบ
        $this->actingAs($admin)->post('/sub-strategies', [
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์ทดสอบ', 'status' => 'enable',
            'reviewers' => [$reviewerId],
        ])->assertRedirect('/sub-strategies');
        $subStrategy = \App\Models\KpiSubStrategy::where('name', 'กลยุทธ์ทดสอบ')->firstOrFail();
        $this->assertEquals(1, $subStrategy->reviewers()->count());

        // 3) สร้างตัวชี้วัด (ปีงบประมาณ + รายไตรมาส) → ต้องได้ 4 targets
        $this->actingAs($admin)->post('/indicators', [
            'sub_strategy_id' => $subStrategy->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดทดสอบ',
            'year_type' => 'fiscal', 'year' => 2569, 'period_type' => 'quarterly', 'unit' => 'ร้อยละ',
            'status' => 'enable', 'owners' => [$admin->id], 'primary_owner' => $admin->id,
        ])->assertRedirect();
        $indicator = KpiIndicator::where('name', 'ตัวชี้วัดทดสอบ')->firstOrFail();
        $this->assertEquals(4, $indicator->targets()->count());
        $this->assertEquals(1, $indicator->owners()->count());

        // ตรวจช่วงไตรมาส 1 ของปีงบประมาณ = 1 ต.ค. 2568 (2025-10-01)
        $q1 = $indicator->targets()->where('period_no', 1)->first();
        $this->assertEquals('2025-10-01', $q1->start_date->format('Y-m-d'));

        // 4) กำหนดค่าเป้าหมาย (ทุกไตรมาส >= 80)
        $targetsPayload = [];
        foreach ($indicator->targets as $t) {
            $targetsPayload[$t->period_no] = ['operator' => 'gte', 'target_value' => 80, 'target_text' => null];
        }
        $this->actingAs($admin)->put("/targets/{$indicator->id}", ['targets' => $targetsPayload])
            ->assertRedirect("/indicators/{$indicator->id}");
        $this->assertEquals(80.0, (float) $q1->fresh()->target_value);

        // 5) บันทึกผลงาน (Q1 = 85 → ผ่าน, Q2 = 70 → ไม่ผ่าน)
        $q2 = $indicator->targets()->where('period_no', 2)->first();
        $this->actingAs($admin)->put("/results/{$indicator->id}", [
            'results' => [
                $q1->id => ['result_value' => 85, 'note' => 'ดี'],
                $q2->id => ['result_value' => 70, 'note' => null],
            ],
        ])->assertRedirect("/indicators/{$indicator->id}");

        $this->assertEquals('pass', KpiResult::where('target_id', $q1->id)->value('pass_status'));
        $this->assertEquals('fail', KpiResult::where('target_id', $q2->id)->value('pass_status'));

        // 6) หน้าแสดงผลต่าง ๆ เปิดได้
        $this->actingAs($admin)->get("/indicators/{$indicator->id}")->assertOk()->assertSee('ตัวชี้วัดทดสอบ');
        $this->actingAs($admin)->get('/reports?year=2569')->assertOk();
        $this->actingAs($admin)->get('/permissions')->assertOk();
        $this->actingAs($admin)->get('/level-managers')->assertOk();
    }

    public function test_permission_denied_without_access(): void
    {
        // ผู้ใช้ที่ไม่มีสิทธิ์เมนูใด ๆ ต้องถูกปฏิเสธ (403) จากหน้าจัดการ
        $plainUser = User::where('id', '!=', 1)->where('id', '!=', 2)->firstOrFail();

        $this->actingAs($plainUser)->get('/indicators')->assertForbidden();
        $this->actingAs($plainUser)->get('/permissions')->assertForbidden();
    }
}
