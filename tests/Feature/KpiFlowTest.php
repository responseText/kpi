<?php

namespace Tests\Feature;

use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use App\Models\KpiLevel;
use App\Models\KpiMain;
use App\Models\KpiResult;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnLevel;
use App\Models\UserOnMenu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
            'year' => 2569, 'level' => 'hospital', 'code' => 'ย.1', 'name' => 'ยุทธศาสตร์ทดสอบ', 'status' => 'enable',
        ])->assertRedirect('/strategies');
        $strategy = KpiStrategy::where('name', 'ยุทธศาสตร์ทดสอบ')->firstOrFail();

        // 2) สร้างกลยุทธ์ + ผู้ตรวจสอบ
        $this->actingAs($admin)->post('/sub-strategies', [
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์ทดสอบ', 'status' => 'enable',
            'reviewers' => [$reviewerId],
        ])->assertRedirect('/sub-strategies');
        $subStrategy = KpiSubStrategy::where('name', 'กลยุทธ์ทดสอบ')->firstOrFail();
        $this->assertEquals(1, $subStrategy->reviewers()->count());

        // 2.5) สร้างหมวด KPI + KPI หลัก (ตัวชี้วัดอยู่ภายใต้ KPI หลัก)
        $category = KpiCategory::create(['sub_strategy_id' => $subStrategy->id, 'name' => 'หมวด KPI ทดสอบ', 'status' => 'enable']);
        $main = KpiMain::create(['category_id' => $category->id, 'name' => 'KPI หลัก ทดสอบ', 'status' => 'enable']);

        // 3) สร้างตัวชี้วัด (ปีงบประมาณ + รายไตรมาส) → ต้องได้ 4 targets
        $this->actingAs($admin)->post('/indicators', [
            'kpi_main_id' => $main->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดทดสอบ',
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

    public function test_strategy_belongs_to_a_level_and_name_unique_per_level(): void
    {
        $admin = $this->admin();

        // สร้างยุทธศาสตร์ระดับโรงพยาบาล ปี 2569
        $this->actingAs($admin)->post('/strategies', [
            'year' => 2569, 'level' => 'hospital', 'name' => 'ยุทธศาสตร์ระดับซ้ำ', 'status' => 'enable',
        ])->assertRedirect('/strategies');
        $this->assertDatabaseHas('kpi_strategies', [
            'year' => 2569, 'level' => 'hospital', 'name' => 'ยุทธศาสตร์ระดับซ้ำ',
        ]);

        // ชื่อซ้ำ + ปีเดียวกัน + ระดับเดียวกัน → ถูกปฏิเสธ
        $this->actingAs($admin)->post('/strategies', [
            'year' => 2569, 'level' => 'hospital', 'name' => 'ยุทธศาสตร์ระดับซ้ำ', 'status' => 'enable',
        ])->assertSessionHasErrors('name');

        // ชื่อเดียวกัน + ปีเดียวกัน แต่คนละระดับ (กระทรวง) → อนุญาต
        $this->actingAs($admin)->post('/strategies', [
            'year' => 2569, 'level' => 'ministry', 'name' => 'ยุทธศาสตร์ระดับซ้ำ', 'status' => 'enable',
        ])->assertRedirect('/strategies');
        $this->assertEquals(
            2,
            KpiStrategy::where('year', 2569)->where('name', 'ยุทธศาสตร์ระดับซ้ำ')->count()
        );

        // ขาดระดับ → validation ไม่ผ่าน
        $this->actingAs($admin)->post('/strategies', [
            'year' => 2569, 'name' => 'ยุทธศาสตร์ไม่มีระดับ', 'status' => 'enable',
        ])->assertSessionHasErrors('level');
    }

    public function test_permission_denied_without_access(): void
    {
        // ผู้ใช้ที่ไม่มีสิทธิ์เมนูใด ๆ ต้องถูกปฏิเสธ (403) จากหน้าจัดการ
        $plainUser = User::where('id', '!=', 1)->where('id', '!=', 2)->firstOrFail();

        $this->actingAs($plainUser)->get('/indicators')->assertForbidden();
        $this->actingAs($plainUser)->get('/permissions')->assertForbidden();
    }

    public function test_result_recording_restricted_to_owner_or_admin(): void
    {
        // เตรียมตัวชี้วัดระดับโรงพยาบาล
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'RT', 'name' => 'ยุทธศาสตร์สิทธิ์ผล', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์สิทธิ์ผล', 'status' => 'enable',
        ]);
        $indicator = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดสิทธิ์ผล',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);

        // ให้สิทธิ์เมนู "บันทึกผลงาน" แก่ผู้ใช้ทั่วไป แต่ยังไม่ใช่ผู้รับผิดชอบ
        $stranger = User::where('id', '!=', 1)->where('id', '!=', 2)->firstOrFail();
        $resultMenu = Menu::where('code', 'kpi.result')->firstOrFail();
        UserOnMenu::updateOrCreate(
            ['user_id' => $stranger->id, 'menu_id' => $resultMenu->id],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_edit' => true],
        );

        // ไม่ใช่ผู้รับผิดชอบ + ไม่มีบทบาทผู้ดูแล → 403
        $this->actingAs($stranger)->get("/results/{$indicator->id}/edit")->assertForbidden();

        // เป็นผู้รับผิดชอบของตัวชี้วัดนี้ → เข้าได้
        $indicator->owners()->attach($stranger->id, ['is_primary' => false]);
        $this->actingAs($stranger->fresh())->get("/results/{$indicator->id}/edit")->assertOk();

        // ผู้ดูแลระบบสูงสุด → เข้าได้เสมอ
        $this->actingAs($this->admin())->get("/results/{$indicator->id}/edit")->assertOk();
    }

    public function test_results_index_lists_only_recordable_indicators(): void
    {
        // เตรียมตัวชี้วัด 2 ระดับ (ใช้ token ร่วม 'IDXMARK' เพื่อค้นหาให้แคบ ไม่ปนข้อมูลเดิม)
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'IX', 'name' => 'ยุทธศาสตร์รายการบันทึกผล', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์รายการบันทึกผล', 'status' => 'enable',
        ]);
        $hospital = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'IDXMARK ตัวชี้วัดรพ',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);
        $ministry = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'ministry', 'name' => 'IDXMARK ตัวชี้วัดกระทรวง',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);

        // ผู้ใช้ทั่วไป มีสิทธิ์ "ดู" เมนูบันทึกผล แต่ยังไม่มีบทบาท/ไม่ใช่ผู้รับผิดชอบ
        $stranger = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $resultMenu = Menu::where('code', 'kpi.result')->firstOrFail();
        UserOnMenu::updateOrCreate(
            ['user_id' => $stranger->id, 'menu_id' => $resultMenu->id],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_edit' => true],
        );

        // ไม่มีบทบาท + ไม่ใช่ผู้รับผิดชอบ → ไม่เห็นรายการใดเลย
        $this->actingAs($stranger->fresh())->get('/results?search=IDXMARK')
            ->assertOk()->assertDontSee('ตัวชี้วัดรพ')->assertDontSee('ตัวชี้วัดกระทรวง');

        // เป็นผู้รับผิดชอบเฉพาะตัวชี้วัดระดับ รพ. → เห็นเฉพาะตัวนั้น
        $hospital->owners()->attach($stranger->id, ['is_primary' => false]);
        $this->actingAs($stranger->fresh())->get('/results?search=IDXMARK')
            ->assertOk()->assertSee('ตัวชี้วัดรพ')->assertDontSee('ตัวชี้วัดกระทรวง');

        // เปลี่ยนเป็นผู้ดูแล "ระดับโรงพยาบาล" (ไม่ใช่ผู้รับผิดชอบ) → เห็นทุกตัวระดับ รพ. แต่ไม่เห็นระดับกระทรวง
        $hospital->owners()->detach($stranger->id);
        $hospitalLevelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');
        UserOnLevel::create(['user_id' => $stranger->id, 'alias_system' => 'kpi', 'level_id' => $hospitalLevelId, 'is_super_admin' => false]);
        $this->actingAs($stranger->fresh())->get('/results?search=IDXMARK')
            ->assertOk()->assertSee('ตัวชี้วัดรพ')->assertDontSee('ตัวชี้วัดกระทรวง');

        // เข้าหน้าบันทึกผลของตัวชี้วัดระดับกระทรวงโดยตรง → ถูกปฏิเสธ (403)
        $this->actingAs($stranger->fresh())->get("/results/{$ministry->id}/edit")->assertForbidden();

        // ผู้ดูแลระบบสูงสุด → เห็นทั้งสองระดับ
        $this->actingAs($this->admin())->get('/results?search=IDXMARK')
            ->assertOk()->assertSee('ตัวชี้วัดรพ')->assertSee('ตัวชี้วัดกระทรวง');
    }

    public function test_indicators_menu_restricted_to_indicator_admins_by_level(): void
    {
        // ตัวชี้วัด 2 ระดับ (token 'INDMARK')
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'IM', 'name' => 'ยุทธศาสตร์จัดการตัวชี้วัด', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์จัดการตัวชี้วัด', 'status' => 'enable',
        ]);
        $category = KpiCategory::create(['sub_strategy_id' => $sub->id, 'name' => 'หมวด KPI จัดการตัวชี้วัด', 'status' => 'enable']);
        $main = KpiMain::create(['category_id' => $category->id, 'name' => 'KPI หลัก จัดการตัวชี้วัด', 'status' => 'enable']);
        $hospital = KpiIndicator::create([
            'kpi_main_id' => $main->id, 'level' => 'hospital', 'name' => 'INDMARK ตชว.รพ',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);
        $ministry = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'ministry', 'name' => 'INDMARK ตชว.กระทรวง',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);

        $hospitalAdmin = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $plain = User::whereNotIn('id', [1, 2, $hospitalAdmin->id])->orderBy('id')->firstOrFail();
        $hospitalLevelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');

        // ผู้ใช้ทั่วไป (ไม่มีบทบาทผู้ดูแล) → เข้าเมนูไม่ได้เลย (403)
        $this->actingAs($plain->fresh())->get('/indicators')->assertForbidden();

        // ผู้ดูแลระดับโรงพยาบาล → เข้าได้ เห็นเฉพาะระดับ รพ.
        UserOnLevel::create(['user_id' => $hospitalAdmin->id, 'alias_system' => 'kpi', 'level_id' => $hospitalLevelId, 'is_super_admin' => false]);
        $this->actingAs($hospitalAdmin->fresh())->get('/indicators?search=INDMARK')
            ->assertOk()->assertSee('ตชว.รพ')->assertDontSee('ตชว.กระทรวง');

        // ยังไม่ได้กำหนดสิทธิ์เมนู "เพิ่ม/แก้ไข" → ดูได้อย่างเดียว (เปิดฟอร์มเพิ่ม/แก้ไม่ได้)
        $this->actingAs($hospitalAdmin->fresh())->get('/indicators/create')->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->get("/indicators/{$hospital->id}/edit")->assertForbidden();

        // กำหนดสิทธิ์ action เมนูตัวชี้วัดให้ผู้ดูแล รพ. (บทบาทคุมขอบเขตระดับ, สิทธิ์เมนูคุมว่าทำ action ได้)
        $indicatorMenu = Menu::where('code', 'kpi.indicator')->firstOrFail();
        UserOnMenu::updateOrCreate(
            ['user_id' => $hospitalAdmin->id, 'menu_id' => $indicatorMenu->id],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
        );

        // ดู/แก้ตัวชี้วัดระดับ รพ. ได้ + ฟอร์มสร้างเปิดได้ แต่ระดับกระทรวงถูกปฏิเสธ (403)
        $this->actingAs($hospitalAdmin->fresh())->get('/indicators/create')->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/indicators/{$hospital->id}")->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/indicators/{$hospital->id}/edit")->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/indicators/{$ministry->id}")->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->get("/indicators/{$ministry->id}/edit")->assertForbidden();

        // สร้างตัวชี้วัดระดับกระทรวง → ถูกปฏิเสธ (สร้างได้เฉพาะระดับของตน)
        $ministryPayload = [
            'kpi_main_id' => $main->id, 'level' => 'ministry', 'name' => 'INDMARK สร้างกระทรวง',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
            'owners' => [$hospitalAdmin->id], 'primary_owner' => $hospitalAdmin->id,
        ];
        $this->actingAs($hospitalAdmin->fresh())->post('/indicators', $ministryPayload)->assertForbidden();

        // สร้างตัวชี้วัดระดับ รพ. → สำเร็จ
        $hospitalPayload = array_merge($ministryPayload, ['level' => 'hospital', 'name' => 'INDMARK สร้างรพ']);
        $this->actingAs($hospitalAdmin->fresh())->post('/indicators', $hospitalPayload)->assertRedirect();
        $this->assertDatabaseHas('kpi_indicators', ['name' => 'INDMARK สร้างรพ', 'level' => 'hospital']);

        // ผู้ดูแลระบบสูงสุด → เห็นทั้งสองระดับ
        $this->actingAs($this->admin())->get('/indicators?search=INDMARK')
            ->assertOk()->assertSee('ตชว.รพ')->assertSee('ตชว.กระทรวง');
    }

    public function test_targets_menu_restricted_to_indicator_admins_by_level(): void
    {
        // ตัวชี้วัด 2 ระดับ (token 'TGTMARK' เพื่อค้นหาให้แคบ)
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'TG', 'name' => 'ยุทธศาสตร์ค่าเป้าหมาย', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์ค่าเป้าหมาย', 'status' => 'enable',
        ]);
        $hospital = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'TGTMARK ตชว.รพ',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);
        $ministry = KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'ministry', 'name' => 'TGTMARK ตชว.กระทรวง',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);

        $hospitalAdmin = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $plain = User::whereNotIn('id', [1, 2, $hospitalAdmin->id])->orderBy('id')->firstOrFail();
        $hospitalLevelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');

        // ผู้ใช้ทั่วไป (ไม่มีบทบาทผู้ดูแล) → เข้าเมนูไม่ได้เลย (403)
        $this->actingAs($plain->fresh())->get('/targets')->assertForbidden();

        // ผู้ดูแลระดับโรงพยาบาล → เข้าได้ เห็นเฉพาะระดับ รพ.
        UserOnLevel::create(['user_id' => $hospitalAdmin->id, 'alias_system' => 'kpi', 'level_id' => $hospitalLevelId, 'is_super_admin' => false]);
        $this->actingAs($hospitalAdmin->fresh())->get('/targets?search=TGTMARK')
            ->assertOk()->assertSee('ตชว.รพ')->assertDontSee('ตชว.กระทรวง');

        // ยังไม่ได้กำหนดสิทธิ์ "แก้ไข" เมนูค่าเป้าหมาย → ดูได้อย่างเดียว (กำหนดค่าเป้าหมายไม่ได้)
        $this->actingAs($hospitalAdmin->fresh())->get("/targets/{$hospital->id}/edit")->assertForbidden();

        // กำหนดสิทธิ์ "แก้ไข" เมนูค่าเป้าหมายให้ผู้ดูแล รพ.
        $targetMenu = Menu::where('code', 'kpi.target')->firstOrFail();
        UserOnMenu::updateOrCreate(
            ['user_id' => $hospitalAdmin->id, 'menu_id' => $targetMenu->id],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_edit' => true],
        );

        // กำหนดค่าเป้าหมายระดับ รพ. ได้ แต่ระดับกระทรวงถูกปฏิเสธ (403)
        $this->actingAs($hospitalAdmin->fresh())->get("/targets/{$hospital->id}/edit")->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/targets/{$ministry->id}/edit")->assertForbidden();

        // ผู้ดูแลระบบสูงสุด → เห็นทั้งสองระดับ + แก้ได้ทุกระดับ
        $this->actingAs($this->admin())->get('/targets?search=TGTMARK')
            ->assertOk()->assertSee('ตชว.รพ')->assertSee('ตชว.กระทรวง');
        $this->actingAs($this->admin())->get("/targets/{$ministry->id}/edit")->assertOk();
    }

    public function test_dashboard_level_submenus_filter_by_level(): void
    {
        $admin = $this->admin();

        // ทุกเมนูย่อยของแดชบอร์ดเปิดได้
        foreach (['/dashboard', '/dashboard/ministry', '/dashboard/province', '/dashboard/hospital'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        // สร้างตัวชี้วัดระดับโรงพยาบาลปี 2569
        $strategy = KpiStrategy::create([
            'year' => 2569, 'code' => 'DB', 'name' => 'ยุทธศาสตร์เมนูแดชบอร์ด', 'status' => 'enable',
        ]);
        $sub = KpiSubStrategy::create([
            'strategy_id' => $strategy->id, 'name' => 'กลยุทธ์เมนูแดชบอร์ด', 'status' => 'enable',
        ]);
        KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดเฉพาะโรงพยาบาลDB',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);

        // ระดับโรงพยาบาลเห็น, ระดับกระทรวงไม่เห็น
        $this->actingAs($admin)->get('/dashboard/hospital?year=2569')->assertOk()->assertSee('ตัวชี้วัดเฉพาะโรงพยาบาลDB');
        $this->actingAs($admin)->get('/dashboard/ministry?year=2569')->assertOk()->assertDontSee('ตัวชี้วัดเฉพาะโรงพยาบาลDB');
    }

    public function test_dashboard_open_to_all_and_excluded_from_permission_table(): void
    {
        // ผู้ใช้ทั่วไป (ไม่มีสิทธิ์เมนูใด ๆ) เข้าแดชบอร์ด/Monitor ได้
        $plain = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $this->actingAs($plain->fresh())->get('/dashboard')->assertOk();
        $this->actingAs($plain->fresh())->get('/dashboard/hospital')->assertOk();
        $this->actingAs($plain->fresh())->get('/monitor')->assertOk();

        // หน้ากำหนดสิทธิ์: ไม่มีแถวจัดการเมนูแดชบอร์ด แต่ยังมีเมนูอื่น (เช่น ตัวชี้วัด)
        $dashboardMenu = Menu::where('code', 'kpi.dashboard')->firstOrFail();
        $indicatorMenu = Menu::where('code', 'kpi.indicator')->firstOrFail();

        $res = $this->actingAs($this->admin())->get("/permissions/{$plain->id}/edit");
        $res->assertOk();
        $res->assertSee("permissions[{$indicatorMenu->id}]", false);
        $res->assertDontSee("permissions[{$dashboardMenu->id}]", false);
    }

    public function test_role_data_lives_in_users_on_level_not_users_table(): void
    {
        // คอลัมน์บทบาทถูกย้ายออกจากตาราง users แล้ว
        $this->assertFalse(Schema::hasColumn('users', 'is_super_admin'));
        $this->assertFalse(Schema::hasColumn('users', 'kpi_level_id'));

        // ผู้ดูแลระบบสูงสุด (user 1) อ่านค่าได้จาก users_on_level
        $this->assertTrue($this->admin()->is_super_admin);
        $this->assertTrue(
            UserOnLevel::where('user_id', 1)->where('alias_system', 'kpi')->where('is_super_admin', true)->exists()
        );
    }

    public function test_indicator_admin_all_can_manage_permissions(): void
    {
        $users = User::whereNotIn('id', [1, 2])->orderBy('id')->take(2)->get();
        $actor = $users[0];
        $target = $users[1];

        $allId = KpiLevel::where('code', KpiLevel::ADMIN_ALL)->value('id');
        $hospitalId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');
        $ownerId = KpiLevel::where('code', KpiLevel::OWNER)->value('id');

        // ผู้ดูแลตัวชี้วัดทั้งหมด → เข้าเมนูกำหนดสิทธิ์ได้
        UserOnLevel::create(['user_id' => $actor->id, 'alias_system' => 'kpi', 'level_id' => $allId, 'is_super_admin' => false]);
        $this->actingAs($actor->fresh())->get('/permissions')->assertOk();

        // และกำหนดบทบาทให้ผู้อื่นได้ — เก็บที่ users_on_level
        $this->actingAs($actor->fresh())->put("/permissions/{$target->id}", [
            'kpi_level_ids' => [$ownerId],
            'permissions' => [],
        ])->assertRedirect(route('permissions.index'));
        $this->assertContains('indicator_owner', $target->fresh()->kpiLevels()->pluck('code')->all());

        // ผู้ดูแล "ระดับโรงพยาบาล" (ไม่ใช่ทั้งหมด) → เข้าเมนูกำหนดสิทธิ์ไม่ได้
        UserOnLevel::where('user_id', $target->id)->where('alias_system', 'kpi')->delete();
        UserOnLevel::create(['user_id' => $target->id, 'alias_system' => 'kpi', 'level_id' => $hospitalId, 'is_super_admin' => false]);
        $this->actingAs($target->fresh())->get('/permissions')->assertForbidden();
    }

    public function test_user_can_hold_multiple_kpi_roles(): void
    {
        $target = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $hospitalId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');
        $ministryId = KpiLevel::where('code', KpiLevel::ADMIN_MINISTRY)->value('id');

        // กำหนด 2 บทบาทพร้อมกันผ่านหน้าจัดการสิทธิ์
        $this->actingAs($this->admin())->put("/permissions/{$target->id}", [
            'kpi_level_ids' => [$hospitalId, $ministryId],
            'permissions' => [],
        ])->assertRedirect(route('permissions.index'));

        $u = $target->fresh();
        $this->assertCount(2, $u->kpiLevels());
        // ขอบเขตการจัดการครอบคลุมทั้งสองระดับ แต่ไม่รวมระดับที่ไม่ได้รับ
        $this->assertTrue($u->canManageIndicatorLevel('hospital'));
        $this->assertTrue($u->canManageIndicatorLevel('ministry'));
        $this->assertFalse($u->canManageIndicatorLevel('province'));
    }

    public function test_level_managers_menu_restricted_to_top_admins(): void
    {
        $allId = KpiLevel::where('code', KpiLevel::ADMIN_ALL)->value('id');
        $hospitalId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');

        $adminAll = User::whereNotIn('id', [1, 2])->orderBy('id')->skip(0)->firstOrFail();
        $levelAdmin = User::whereNotIn('id', [1, 2, $adminAll->id])->orderBy('id')->firstOrFail();

        // ผู้ดูแลระบบสูงสุด → เข้าได้
        $this->actingAs($this->admin())->get('/level-managers')->assertOk();

        // ผู้ดูแลตัวชี้วัดทั้งหมด → เข้าได้
        UserOnLevel::create(['user_id' => $adminAll->id, 'alias_system' => 'kpi', 'level_id' => $allId, 'is_super_admin' => false]);
        $this->actingAs($adminAll->fresh())->get('/level-managers')->assertOk();

        // ผู้ดูแลระดับ (โรงพยาบาล) → ถูกปฏิเสธ (403)
        UserOnLevel::create(['user_id' => $levelAdmin->id, 'alias_system' => 'kpi', 'level_id' => $hospitalId, 'is_super_admin' => false]);
        $this->actingAs($levelAdmin->fresh())->get('/level-managers')->assertForbidden();

        // ผู้ใช้ทั่วไป (ไม่มีบทบาท) → ถูกปฏิเสธ (403)
        $plain = User::whereNotIn('id', [1, 2, $adminAll->id, $levelAdmin->id])->orderBy('id')->firstOrFail();
        $this->actingAs($plain->fresh())->get('/level-managers')->assertForbidden();
    }

    public function test_user_can_edit_own_profile_and_password(): void
    {
        $user = User::where('id', '!=', 1)->where('id', '!=', 2)->firstOrFail();

        // เปิดหน้าข้อมูลส่วนตัวได้ (ไม่ต้องมีสิทธิ์เมนู)
        $this->actingAs($user)->get('/profile')->assertOk();

        // แก้ไขอีเมลของตัวเอง
        $this->actingAs($user)->put('/profile', ['email' => 'me.profile@example.com'])
            ->assertRedirect('/profile');
        $this->assertEquals('me.profile@example.com', $user->fresh()->email);

        // ตั้งรหัสผ่านที่ทราบค่าไว้สำหรับทดสอบ
        $user->forceFill(['password' => Hash::make('OldPass12345')])->save();

        // รหัสผ่านปัจจุบันผิด → ถูกปฏิเสธ
        $this->actingAs($user->fresh())->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPass12345',
            'password_confirmation' => 'NewPass12345',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPass12345', $user->fresh()->password));

        // รหัสผ่านปัจจุบันถูก + ยืนยันตรงกัน → เปลี่ยนสำเร็จ
        $this->actingAs($user->fresh())->put('/profile/password', [
            'current_password' => 'OldPass12345',
            'password' => 'NewPass12345',
            'password_confirmation' => 'NewPass12345',
        ])->assertRedirect('/profile');
        $this->assertTrue(Hash::check('NewPass12345', $user->fresh()->password));
    }

    public function test_user_management_menu_restricted_to_super_admin(): void
    {
        $allId = KpiLevel::where('code', KpiLevel::ADMIN_ALL)->value('id');

        // ผู้ดูแลระบบสูงสุด → เข้าเมนู "จัดการผู้ใช้งาน" ได้
        $this->actingAs($this->admin())->get('/users')->assertOk();

        // ผู้ดูแลตัวชี้วัดทั้งหมด (ไม่ใช่ super admin) → ถูกปฏิเสธ (403) เพราะเมนูนี้เฉพาะผู้ดูแลระบบสูงสุด
        $adminAll = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        UserOnLevel::create(['user_id' => $adminAll->id, 'alias_system' => 'kpi', 'level_id' => $allId, 'is_super_admin' => false]);
        $this->actingAs($adminAll->fresh())->get('/users')->assertForbidden();

        // ผู้ใช้ทั่วไป → ถูกปฏิเสธ (403)
        $plain = User::whereNotIn('id', [1, 2, $adminAll->id])->orderBy('id')->firstOrFail();
        $this->actingAs($plain->fresh())->get('/users')->assertForbidden();
    }

    public function test_super_admin_manages_other_user_password_status_and_level(): void
    {
        $admin = $this->admin();
        $target = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $ownerId = KpiLevel::where('code', KpiLevel::OWNER)->value('id');
        $superId = KpiLevel::where('code', KpiLevel::SUPER_ADMIN)->value('id');

        $target->forceFill(['status' => 'enable'])->save();
        $this->actingAs($admin)->get("/users/{$target->id}/edit")->assertOk();

        // อัปเดตสถานะ (ปิดใช้งาน) + ระดับ (ผู้รับผิดชอบ)
        $this->actingAs($admin)->put("/users/{$target->id}", [
            'status' => 'disable',
            'kpi_level_ids' => [$ownerId],
        ])->assertRedirect(route('users.edit', $target));
        $this->assertEquals('disable', $target->fresh()->status);
        $this->assertContains('indicator_owner', $target->fresh()->kpiLevels()->pluck('code')->all());

        // รีเซ็ตรหัสผ่าน (ผู้ดูแลระบบสูงสุดตั้งให้ได้โดยไม่ต้องใช้รหัสเดิม)
        $this->actingAs($admin)->put("/users/{$target->id}/password", [
            'password' => 'ResetPass12345',
            'password_confirmation' => 'ResetPass12345',
        ])->assertRedirect(route('users.edit', $target));
        $this->assertTrue(Hash::check('ResetPass12345', $target->fresh()->password));

        // ยืนยันรหัสผ่านไม่ตรง → ถูกปฏิเสธ
        $this->actingAs($admin)->put("/users/{$target->id}/password", [
            'password' => 'AnotherPass123',
            'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors('password');

        // ห้ามกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้
        $this->actingAs($admin)->put("/users/{$target->id}", [
            'status' => 'enable',
            'kpi_level_ids' => [$superId],
        ])->assertSessionHas('error');
        $this->assertFalse($target->fresh()->is_super_admin);

        // ห้ามจัดการบัญชีของผู้ดูแลระบบสูงสุด (user 1)
        $this->actingAs($admin)->put('/users/1', ['status' => 'disable', 'kpi_level_ids' => []])
            ->assertSessionHas('error');
        $this->assertEquals('enable', User::find(1)->status);
        $this->actingAs($admin)->put('/users/1/password', [
            'password' => 'HackSuper12345', 'password_confirmation' => 'HackSuper12345',
        ])->assertSessionHas('error');
    }
}
