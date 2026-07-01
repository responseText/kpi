<?php

namespace Tests\Feature;

use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use App\Models\KpiMain;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\User;
use App\Services\Import\ImportRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * ระบบนำเข้าข้อมูล KPI จากไฟล์ Excel — สิทธิ์ / เทมเพลต / การนำเข้าทั้งสาย
 */
class ImportTest extends TestCase
{
    use DatabaseTransactions;

    private const Y = 2599;   // ปีทดสอบ (ไม่ชนข้อมูลจริง)

    private function admin(): User
    {
        return User::findOrFail(1);   // ผู้ดูแลระบบสูงสุด
    }

    private function plainUser(): User
    {
        return User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
    }

    /** สร้างไฟล์ .xlsx (ชีต "ข้อมูล") จาก key ประเภท + แถวข้อมูล (assoc ตาม key คอลัมน์) */
    private function upload(string $typeKey, array $rows): UploadedFile
    {
        $type = app(ImportRegistry::class)->get($typeKey);
        $columns = $type->columns();

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('ข้อมูล');

        foreach ($columns as $i => $col) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $col->header);
        }

        $r = 2;
        foreach ($rows as $row) {
            foreach ($columns as $i => $col) {
                if (array_key_exists($col->key, $row) && $row[$col->key] !== null) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $r, $row[$col->key]);
                }
            }
            $r++;
        }

        $path = tempnam(sys_get_temp_dir(), 'imp_') . '.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile(
            $path, 'data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null, true
        );
    }

    public function test_import_menu_restricted_to_top_admin(): void
    {
        $this->actingAs($this->plainUser()->fresh())->get('/imports')->assertForbidden();
        $this->actingAs($this->admin())->get('/imports')->assertOk();
    }

    public function test_template_download_for_every_type(): void
    {
        foreach (app(ImportRegistry::class)->all() as $type) {
            $res = $this->actingAs($this->admin())->get('/imports/' . $type->key() . '/template');
            $res->assertOk();
            $res->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }

    public function test_unknown_type_returns_404(): void
    {
        $this->actingAs($this->admin())->get('/imports/not-a-type/template')->assertNotFound();
    }

    public function test_full_chain_import_strategy_to_target(): void
    {
        $admin = $this->admin();

        // 1) ยุทธศาสตร์
        $this->actingAs($admin)->post('/imports/strategies', [
            'file' => $this->upload('strategies', [[
                'year' => self::Y, 'level' => 'hospital', 'code' => 'IMPT-S1',
                'name' => 'IMPT ยุทธศาสตร์', 'status' => 'enable',
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $strategy = KpiStrategy::where('year', self::Y)->where('code', 'IMPT-S1')->firstOrFail();
        $this->assertSame('hospital', $strategy->level);

        // 2) กลยุทธ์ (อ้างยุทธศาสตร์ด้วยรหัส)
        $this->actingAs($admin)->post('/imports/sub-strategies', [
            'file' => $this->upload('sub-strategies', [[
                'year' => self::Y, 'level' => 'hospital', 'strategy_ref' => 'IMPT-S1',
                'code' => 'IMPT-SS1', 'name' => 'IMPT กลยุทธ์', 'status' => 'enable',
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $sub = KpiSubStrategy::where('code', 'IMPT-SS1')->firstOrFail();
        $this->assertSame($strategy->id, $sub->strategy_id);

        // 3) หมวด KPI
        $this->actingAs($admin)->post('/imports/categories', [
            'file' => $this->upload('categories', [[
                'year' => self::Y, 'level' => 'hospital', 'sub_strategy_ref' => 'IMPT-SS1',
                'code' => 'IMPT-C1', 'name' => 'IMPT หมวด', 'status' => 'enable',
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $category = KpiCategory::where('code', 'IMPT-C1')->firstOrFail();
        $this->assertSame($sub->id, $category->sub_strategy_id);

        // 4) KPI หลัก
        $this->actingAs($admin)->post('/imports/mains', [
            'file' => $this->upload('mains', [[
                'year' => self::Y, 'level' => 'hospital', 'category_ref' => 'IMPT-C1',
                'code' => 'IMPT-M1', 'name' => 'IMPT main', 'status' => 'enable',
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $main = KpiMain::where('code', 'IMPT-M1')->firstOrFail();
        $this->assertSame($category->id, $main->category_id);

        // 5) ตัวชี้วัด (percent → ต้องมี A/B)
        $this->actingAs($admin)->post('/imports/indicators', [
            'file' => $this->upload('indicators', [[
                'year' => self::Y, 'level' => 'hospital', 'main_ref' => 'IMPT-M1',
                'code' => 'IMPT-KPI1', 'name' => 'IMPT ตัวชี้วัด',
                'year_type' => 'fiscal', 'period_type' => 'annual', 'unit' => 'ร้อยละ',
                'measurement_type' => 'percent', 'numerator_label' => 'ตัวตั้ง', 'denominator_label' => 'ตัวหาร',
                'status' => 'enable',
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $indicator = KpiIndicator::where('code', 'IMPT-KPI1')->where('year', self::Y)->firstOrFail();
        $this->assertSame($main->id, $indicator->kpi_main_id);
        $this->assertSame('percent', $indicator->measurement_type);
        // สร้างช่วงเป้าหมายรายปีให้แล้ว (period_no = 0)
        $this->assertSame(1, $indicator->targets()->count());

        // 6) ค่าเป้าหมาย (เว้น period_no = ทุกช่วง)
        $this->actingAs($admin)->post('/imports/targets', [
            'file' => $this->upload('targets', [[
                'year' => self::Y, 'level' => 'hospital', 'indicator_ref' => 'IMPT-KPI1',
                'operator' => 'gte', 'target_value' => 80,
            ]]),
        ])->assertRedirectContains(route('imports.index'));

        $target = $indicator->targets()->where('period_no', 0)->firstOrFail();
        $this->assertSame('gte', $target->operator);
        $this->assertEquals(80, (float) $target->target_value);
    }

    public function test_strategy_import_is_idempotent(): void
    {
        $admin = $this->admin();
        $payload = fn () => ['file' => $this->upload('strategies', [[
            'year' => self::Y, 'level' => 'province', 'code' => 'IMPT-IDEM',
            'name' => 'IMPT idem', 'status' => 'enable',
        ]])];

        $this->actingAs($admin)->post('/imports/strategies', $payload());
        $this->actingAs($admin)->post('/imports/strategies', $payload());

        // นำเข้าซ้ำต้องไม่เกิดรายการซ้ำ
        $this->assertSame(1, KpiStrategy::where('year', self::Y)->where('code', 'IMPT-IDEM')->count());
    }

    public function test_invalid_rows_write_nothing_and_report_errors(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post('/imports/strategies', [
            'file' => $this->upload('strategies', [[
                'year' => self::Y, 'level' => 'INVALID-LEVEL', 'code' => 'IMPT-BAD',
                'name' => 'IMPT bad row', 'status' => 'enable',
            ]]),
        ]);

        $res->assertRedirectContains(route('imports.index'));
        $res->assertSessionHas('import_result');

        // ระดับผิด → ไม่บันทึกอะไรเลย
        $this->assertSame(0, KpiStrategy::where('code', 'IMPT-BAD')->count());
        $result = session('import_result');
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }
}
