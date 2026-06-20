<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * ประเภทการวัด (Measurement Type) ของตัวชี้วัด + เงื่อนไข ต้องมี A/B/สูตร/ค่าคงที่ K
 * ตามตารางหลักการบริหารผลงาน (Performance Measurement)
 */
class IndicatorMeasurementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    /** สร้างกลยุทธ์ให้ใช้เป็นที่อยู่ของตัวชี้วัด แล้วคืน id */
    private function makeSubStrategy(): int
    {
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'MT', 'name' => 'ยุทธศาสตร์ประเภทการวัดMTEST', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์ประเภทการวัดMTEST', 'status' => 'enable',
        ]);

        return $sub->id;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sub_strategy_id' => $this->makeSubStrategy(),
            'level' => 'hospital',
            'name' => 'ตัวชี้วัดประเภทการวัดMTEST',
            'year_type' => 'fiscal',
            'year' => 2569,
            'period_type' => 'annual',
            'status' => 'enable',
            'owners' => [$this->admin()->id],
            'primary_owner' => $this->admin()->id,
        ], $overrides);
    }

    public function test_percent_requires_numerator_and_denominator(): void
    {
        $admin = $this->admin();

        // PERCENT แต่ไม่ระบุ A/B → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'percent', 'unit' => 'ร้อยละ',
        ]))->assertSessionHasErrors(['numerator_label', 'denominator_label']);

        // ระบุครบ → สำเร็จ + เก็บค่า
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'percent', 'unit' => 'ร้อยละ',
            'numerator_label' => 'จำนวนผ่านเกณฑ์', 'denominator_label' => 'จำนวนทั้งหมด',
        ]))->assertRedirect();

        $this->assertDatabaseHas('kpi_indicators', [
            'name' => 'ตัวชี้วัดประเภทการวัดMTEST', 'measurement_type' => 'percent',
            'numerator_label' => 'จำนวนผ่านเกณฑ์', 'denominator_label' => 'จำนวนทั้งหมด',
        ]);
    }

    public function test_count_needs_no_numerator_or_denominator(): void
    {
        // COUNT ไม่ต้องมี A/B → สำเร็จ และค่า A/B ถูกล้างเป็น null แม้ส่งมา
        $this->actingAs($this->admin())->post('/indicators', $this->payload([
            'measurement_type' => 'count', 'unit' => 'ครั้ง',
            'numerator_label' => 'ค่าขยะที่ไม่ควรเก็บ',
        ]))->assertRedirect();

        $this->assertDatabaseHas('kpi_indicators', [
            'name' => 'ตัวชี้วัดประเภทการวัดMTEST', 'measurement_type' => 'count',
            'numerator_label' => null, 'denominator_label' => null, 'formula' => null,
        ]);
    }

    public function test_index_requires_formula(): void
    {
        $admin = $this->admin();

        // INDEX แต่ไม่ระบุสูตร → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'index', 'unit' => 'ดัชนี',
        ]))->assertSessionHasErrors('formula');

        // ระบุสูตร → สำเร็จ
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'index', 'unit' => 'ดัชนี', 'formula' => '(x1+x2)/2 ปรับถ่วงน้ำหนัก',
        ]))->assertRedirect();

        $this->assertDatabaseHas('kpi_indicators', [
            'name' => 'ตัวชี้วัดประเภทการวัดMTEST', 'measurement_type' => 'index',
            'formula' => '(x1+x2)/2 ปรับถ่วงน้ำหนัก',
        ]);
    }

    public function test_rate_requires_factor_and_stores_it(): void
    {
        $admin = $this->admin();

        // RATE มี A/B แต่ไม่ระบุค่าคงที่ K → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'rate', 'unit' => 'อัตรา',
            'numerator_label' => 'จำนวนผู้เสียชีวิต', 'denominator_label' => 'จำนวนประชากร',
        ]))->assertSessionHasErrors('factor');

        // ระบุ K → สำเร็จ + เก็บค่า
        $this->actingAs($admin)->post('/indicators', $this->payload([
            'measurement_type' => 'rate', 'unit' => 'อัตรา',
            'numerator_label' => 'จำนวนผู้เสียชีวิต', 'denominator_label' => 'จำนวนประชากร',
            'factor' => 100000,
        ]))->assertRedirect();

        $indicator = KpiIndicator::where('name', 'ตัวชี้วัดประเภทการวัดMTEST')
            ->where('measurement_type', 'rate')->firstOrFail();
        $this->assertEquals(100000.0, (float) $indicator->factor);
        // สูตรแสดงผลแทนค่า K
        $this->assertEquals('(A/B)×100,000', $indicator->formula_display);
    }

    public function test_invalid_measurement_type_rejected(): void
    {
        $this->actingAs($this->admin())->post('/indicators', $this->payload([
            'measurement_type' => 'not_a_real_type',
        ]))->assertSessionHasErrors('measurement_type');
    }

    public function test_create_form_lists_measurement_types(): void
    {
        $res = $this->actingAs($this->admin())->get('/indicators/create');
        $res->assertOk();
        $res->assertSee('ประเภทการวัด (Measurement Type)');
        $res->assertSee('ร้อยละ (Percent)');
        $res->assertSee('นับจำนวน (Count)');
        $res->assertSee('ระยะเวลา (Duration)');
    }
}
