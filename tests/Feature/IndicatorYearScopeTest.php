<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiLevel;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnLevel;
use App\Models\UserOnMenu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * บทบาทผู้ดูแลรายระดับผูกกับ "ปีที่รับผิดชอบ":
 * เข้าถึง/จัดการได้เฉพาะข้อมูลของระดับ+ปีที่กำหนด (null = ทุกปี)
 */
class IndicatorYearScopeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    /** ผู้ดูแลระดับ รพ. ผูกปีที่กำหนด (year=null → ทุกปี) */
    private function hospitalAdmin(?int $year): User
    {
        $user = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $levelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');
        UserOnLevel::create([
            'user_id' => $user->id, 'alias_system' => 'kpi',
            'level_id' => $levelId, 'year' => $year, 'is_super_admin' => false,
        ]);

        return $user->fresh();
    }

    private function grantMenu(User $user, string $code): void
    {
        $menuId = Menu::where('code', $code)->value('id');
        UserOnMenu::updateOrCreate(
            ['user_id' => $user->id, 'menu_id' => $menuId],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
        );
    }

    private function makeIndicator(int $year, string $name): KpiIndicator
    {
        $strategy = KpiStrategy::create(['year' => $year, 'level' => 'hospital', 'code' => 'YS', 'name' => "ยุทธ์{$name}", 'status' => 'enable']);
        $sub = KpiSubStrategy::create(['strategy_id' => $strategy->id, 'name' => "กลยุทธ์{$name}", 'status' => 'enable']);

        return KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => $name,
            'year_type' => 'buddhist', 'year' => $year, 'period_type' => 'annual', 'status' => 'enable',
        ]);
    }

    public function test_year_scoped_admin_sees_and_manages_only_assigned_year(): void
    {
        $a2569 = $this->makeIndicator(2569, 'YRMARK ตชว.2569');
        $a2570 = $this->makeIndicator(2570, 'YRMARK ตชว.2570');

        $admin = $this->hospitalAdmin(2569);
        $this->grantMenu($admin, 'kpi.indicator');

        // เห็นเฉพาะปี 2569
        $this->actingAs($admin->fresh())->get('/indicators?search=YRMARK')
            ->assertOk()->assertSee('ตชว.2569')->assertDontSee('ตชว.2570');

        // ดู/แก้ปี 2569 ได้ แต่ปี 2570 ถูกปฏิเสธ (ทั้งดูและจัดการ)
        $this->actingAs($admin->fresh())->get("/indicators/{$a2569->id}")->assertOk();
        $this->actingAs($admin->fresh())->get("/indicators/{$a2569->id}/edit")->assertOk();
        $this->actingAs($admin->fresh())->get("/indicators/{$a2570->id}")->assertForbidden();
        $this->actingAs($admin->fresh())->get("/indicators/{$a2570->id}/edit")->assertForbidden();
    }

    public function test_year_scoped_admin_cannot_create_outside_assigned_year(): void
    {
        $sub = KpiSubStrategy::create([
            'strategy_id' => KpiStrategy::create(['year' => 2569, 'level' => 'hospital', 'name' => 'ยุทธ์สร้าง', 'status' => 'enable'])->id,
            'name' => 'กลยุทธ์สร้าง', 'status' => 'enable',
        ]);

        $admin = $this->hospitalAdmin(2569);
        $this->grantMenu($admin, 'kpi.indicator');

        $base = [
            'sub_strategy_id' => $sub->id, 'level' => 'hospital',
            'year_type' => 'buddhist', 'period_type' => 'annual', 'status' => 'enable',
            'owners' => [$admin->id], 'primary_owner' => $admin->id,
        ];

        // สร้างปี 2570 (นอกปีที่รับผิดชอบ) → 403
        $this->actingAs($admin->fresh())->post('/indicators', array_merge($base, ['name' => 'YRC ปี2570', 'year' => 2570]))
            ->assertForbidden();

        // สร้างปี 2569 → สำเร็จ
        $this->actingAs($admin->fresh())->post('/indicators', array_merge($base, ['name' => 'YRC ปี2569', 'year' => 2569]))
            ->assertRedirect();
        $this->assertDatabaseHas('kpi_indicators', ['name' => 'YRC ปี2569', 'year' => 2569]);
    }

    public function test_all_years_role_sees_every_year(): void
    {
        $this->makeIndicator(2569, 'ALLYR ตชว.2569');
        $this->makeIndicator(2570, 'ALLYR ตชว.2570');

        // year = null → ทุกปี
        $admin = $this->hospitalAdmin(null);
        $this->grantMenu($admin, 'kpi.indicator');

        $this->actingAs($admin->fresh())->get('/indicators?search=ALLYR')
            ->assertOk()->assertSee('ตชว.2569')->assertSee('ตชว.2570');
    }

    public function test_permission_update_stores_responsible_years(): void
    {
        $plain = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $hospitalLevelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');

        // กำหนดบทบาทผู้ดูแล รพ. รับผิดชอบปี 2569 + 2570
        $this->actingAs($this->admin())->put("/permissions/{$plain->id}", [
            'kpi_level_ids' => [$hospitalLevelId],
            'kpi_level_years' => [$hospitalLevelId => ['2569', '2570']],
        ])->assertRedirect('/permissions');

        $this->assertDatabaseHas('users_on_level', ['user_id' => $plain->id, 'level_id' => $hospitalLevelId, 'year' => 2569]);
        $this->assertDatabaseHas('users_on_level', ['user_id' => $plain->id, 'level_id' => $hospitalLevelId, 'year' => 2570]);
        $this->assertDatabaseMissing('users_on_level', ['user_id' => $plain->id, 'level_id' => $hospitalLevelId, 'year' => null]);

        // เปลี่ยนเป็น "ทุกปี" → เหลือแถวเดียว year = null
        $this->actingAs($this->admin())->put("/permissions/{$plain->id}", [
            'kpi_level_ids' => [$hospitalLevelId],
            'kpi_level_years' => [$hospitalLevelId => ['all']],
        ])->assertRedirect('/permissions');

        $this->assertDatabaseHas('users_on_level', ['user_id' => $plain->id, 'level_id' => $hospitalLevelId, 'year' => null]);
        $this->assertDatabaseMissing('users_on_level', ['user_id' => $plain->id, 'level_id' => $hospitalLevelId, 'year' => 2569]);
    }
}
