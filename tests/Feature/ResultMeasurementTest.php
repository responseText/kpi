<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiResult;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * การบันทึกผลแบบกรอกตัวตั้ง (A)/ตัวหาร (B) แล้วระบบคำนวณ result_value อัตโนมัติ
 * (สำหรับตัวชี้วัดประเภท percent/rate/average/ratio)
 */
class ResultMeasurementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    private function makeIndicator(array $attrs): KpiIndicator
    {
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'RM', 'name' => 'ยุทธศาสตร์บันทึกABMTEST', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์บันทึกABMTEST', 'status' => 'enable',
        ]);

        return KpiIndicator::create(array_merge([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดบันทึกABMTEST',
            'year_type' => 'fiscal', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ], $attrs));
    }

    private function makeTarget(KpiIndicator $ind, string $operator, float $value): KpiTarget
    {
        return KpiTarget::create([
            'indicator_id' => $ind->id, 'period_no' => 0, 'period_label' => 'รายปี',
            'start_date' => '2025-10-01', 'end_date' => '2026-09-30',
            'operator' => $operator, 'target_value' => $value,
        ]);
    }

    public function test_percent_result_is_computed_from_a_and_b(): void
    {
        $ind = $this->makeIndicator([
            'measurement_type' => 'percent', 'unit' => 'ร้อยละ',
            'numerator_label' => 'จำนวนผ่านเกณฑ์ABLBL', 'denominator_label' => 'จำนวนทั้งหมดABLBL',
        ]);
        $target = $this->makeTarget($ind, 'gte', 80);

        // กรอก A=85, B=100 → ผล (85/100)×100 = 85 → ผ่านเกณฑ์ ≥ 80
        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['numerator_value' => 85, 'denominator_value' => 100, 'note' => null]],
        ])->assertRedirect("/indicators/{$ind->id}");

        $res = KpiResult::where('target_id', $target->id)->firstOrFail();
        $this->assertEquals(85.0, (float) $res->result_value);
        $this->assertEquals(85.0, (float) $res->numerator_value);
        $this->assertEquals(100.0, (float) $res->denominator_value);
        $this->assertEquals('pass', $res->pass_status);
    }

    public function test_rate_result_uses_factor_k(): void
    {
        $ind = $this->makeIndicator([
            'measurement_type' => 'rate', 'unit' => 'อัตรา', 'factor' => 100000,
            'numerator_label' => 'จำนวนผู้เสียชีวิต', 'denominator_label' => 'จำนวนประชากร',
        ]);
        // เกณฑ์ "ยิ่งน้อยยิ่งดี" ≤ 5 ต่อแสน
        $target = $this->makeTarget($ind, 'lte', 5);

        // A=2, B=50000 → (2/50000)×100000 = 4 → ผ่านเกณฑ์ ≤ 5
        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['numerator_value' => 2, 'denominator_value' => 50000, 'note' => null]],
        ])->assertRedirect("/indicators/{$ind->id}");

        $res = KpiResult::where('target_id', $target->id)->firstOrFail();
        $this->assertEquals(4.0, (float) $res->result_value);
        $this->assertEquals('pass', $res->pass_status);
    }

    public function test_zero_denominator_leaves_result_pending_but_keeps_inputs(): void
    {
        $ind = $this->makeIndicator(['measurement_type' => 'percent', 'unit' => 'ร้อยละ',
            'numerator_label' => 'A', 'denominator_label' => 'B']);
        $target = $this->makeTarget($ind, 'gte', 80);

        // B = 0 → คำนวณไม่ได้ → result_value ว่าง, สถานะ pending แต่ยังเก็บ A,B ที่กรอก
        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['numerator_value' => 10, 'denominator_value' => 0, 'note' => 'B เป็นศูนย์']],
        ])->assertRedirect("/indicators/{$ind->id}");

        $res = KpiResult::where('target_id', $target->id)->firstOrFail();
        $this->assertNull($res->result_value);
        $this->assertEquals(10.0, (float) $res->numerator_value);
        $this->assertEquals('pending', $res->pass_status);
    }

    public function test_count_indicator_still_records_single_value(): void
    {
        $ind = $this->makeIndicator(['measurement_type' => 'count', 'unit' => 'ครั้ง']);
        $target = $this->makeTarget($ind, 'gte', 10);

        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['result_value' => 12, 'note' => null]],
        ])->assertRedirect("/indicators/{$ind->id}");

        $res = KpiResult::where('target_id', $target->id)->firstOrFail();
        $this->assertEquals(12.0, (float) $res->result_value);
        $this->assertNull($res->numerator_value);
        $this->assertEquals('pass', $res->pass_status);
    }

    public function test_edit_form_shows_ab_fields_for_percent(): void
    {
        $ind = $this->makeIndicator([
            'measurement_type' => 'percent', 'unit' => 'ร้อยละ',
            'numerator_label' => 'จำนวนผ่านเกณฑ์ABLBL', 'denominator_label' => 'จำนวนทั้งหมดABLBL',
        ]);
        // ต้องกำหนดค่าเป้าหมายก่อน ช่องกรอก A/B จึงจะแสดง (ไม่กำหนดเป้า = บันทึกผลไม่ได้)
        $this->makeTarget($ind, 'gte', 80);

        $res = $this->actingAs($this->admin())->get("/results/{$ind->id}/edit");
        $res->assertOk();
        $res->assertSee('จำนวนผ่านเกณฑ์ABLBL');   // ป้ายตัวตั้ง (A)
        $res->assertSee('จำนวนทั้งหมดABLBL');      // ป้ายตัวหาร (B)
        $res->assertSee('ผลคำนวณ');                 // ช่องผลคำนวณอัตโนมัติ
    }

    public function test_undefined_target_shows_warning_and_blocks_recording(): void
    {
        // ตัวชี้วัดที่ยังไม่ได้กำหนดค่าเป้าหมาย (syncPeriods สร้างช่วงให้ แต่ operator/target_value ยังว่าง)
        $ind = $this->makeIndicator(['measurement_type' => 'count', 'unit' => 'ครั้ง']);

        // หน้าแก้ไขต้องแจ้งเตือนว่ายังไม่ได้กำหนดค่าเป้าหมาย
        $res = $this->actingAs($this->admin())->get("/results/{$ind->id}/edit");
        $res->assertOk();
        $res->assertSee('ยังไม่ได้กำหนดค่าเป้าหมาย');
        $res->assertSee('จึงยังไม่สามารถบันทึกผลงานได้');

        // แม้ submit ตรงๆ ระบบก็ต้องไม่บันทึกผลของช่วงที่ยังไม่ได้กำหนดเป้า
        $target = $ind->targets()->firstOrFail();   // ช่วงที่ syncPeriods สร้าง (ยัง isDefined()=false)
        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['result_value' => 12, 'note' => null]],
        ])->assertRedirect("/indicators/{$ind->id}");

        $this->assertDatabaseMissing('kpi_results', ['target_id' => $target->id]);
    }

    public function test_edit_form_trims_trailing_decimal_zeros(): void
    {
        $ind = $this->makeIndicator([
            'measurement_type' => 'percent', 'unit' => 'ร้อยละ',
            'numerator_label' => 'A', 'denominator_label' => 'B',
        ]);
        $target = $this->makeTarget($ind, 'gte', 80);

        // บันทึก A=85, B=100 (จำนวนเต็ม ไม่มีทศนิยม)
        $this->actingAs($this->admin())->put("/results/{$ind->id}", [
            'results' => [$target->id => ['numerator_value' => 85, 'denominator_value' => 100, 'note' => null]],
        ])->assertRedirect("/indicators/{$ind->id}");

        // ฟอร์มต้องแสดง 85 / 100 ไม่ใช่ 85.0000 / 100.0000
        $res = $this->actingAs($this->admin())->get("/results/{$ind->id}/edit");
        $res->assertOk();
        $res->assertSee('value="85"', false);
        $res->assertSee('value="100"', false);
        $res->assertDontSee('85.0000');
        $res->assertDontSee('100.0000');
    }
}
