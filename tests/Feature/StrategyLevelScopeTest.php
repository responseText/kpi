<?php

namespace Tests\Feature;

use App\Models\KpiLevel;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnLevel;
use App\Models\UserOnMenu;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * เมนูยุทธศาสตร์/กลยุทธ์ ถูกสโคปตามระดับเหมือนเมนูตัวชี้วัด:
 * ผู้ดูแลรายระดับ (รพ./จังหวัด/กระทรวง) เห็น+จัดการได้เฉพาะระดับของตน
 * ยกเว้นผู้ดูแลตัวชี้วัดทั้งหมด/ระบบสูงสุด เข้าได้ทุกระดับ
 */
class StrategyLevelScopeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    /** ผู้ดูแลระดับโรงพยาบาล (assign บทบาท ADMIN_HOSPITAL ใน transaction) */
    private function hospitalAdmin(): User
    {
        $user = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        $levelId = KpiLevel::where('code', KpiLevel::ADMIN_HOSPITAL)->value('id');
        UserOnLevel::create(['user_id' => $user->id, 'alias_system' => 'kpi', 'level_id' => $levelId, 'is_super_admin' => false]);

        return $user->fresh();
    }

    private function plainUser(User $exclude): User
    {
        return User::whereNotIn('id', [1, 2, $exclude->id])->orderBy('id')->firstOrFail();
    }

    /** กำหนดสิทธิ์ action (เพิ่ม/แก้ไข/ลบ) ของเมนูให้ผู้ใช้ */
    private function grantMenu(User $user, string $code): void
    {
        $menuId = Menu::where('code', $code)->value('id');
        UserOnMenu::updateOrCreate(
            ['user_id' => $user->id, 'menu_id' => $menuId],
            ['alias_system' => 'kpi', 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
        );
    }

    private function makeStrategy(string $level, string $name): KpiStrategy
    {
        return KpiStrategy::create([
            'year' => 2569, 'level' => $level, 'code' => 'SLT', 'name' => $name, 'status' => 'enable',
        ]);
    }

    public function test_strategies_scoped_to_admin_level(): void
    {
        $hospital = $this->makeStrategy('hospital', 'SLTMARK ยุทธศาสตร์รพ');
        $ministry = $this->makeStrategy('ministry', 'SLTMARK ยุทธศาสตร์กระทรวง');

        $hospitalAdmin = $this->hospitalAdmin();
        $plain = $this->plainUser($hospitalAdmin);

        // ผู้ใช้ทั่วไป (ไม่มีบทบาท) → เข้าเมนูไม่ได้
        $this->actingAs($plain->fresh())->get('/strategies')->assertForbidden();

        // ผู้ดูแล รพ. → เห็นเฉพาะระดับ รพ. (ดูได้ตามบทบาท)
        $this->actingAs($hospitalAdmin->fresh())->get('/strategies?year=2569')
            ->assertOk()->assertSee('ยุทธศาสตร์รพ')->assertDontSee('ยุทธศาสตร์กระทรวง');

        // บทบาทอย่างเดียว ยังไม่ได้กำหนดสิทธิ์เมนู → ดูได้อย่างเดียว (เพิ่ม/แก้ไม่ได้)
        $this->actingAs($hospitalAdmin->fresh())->get('/strategies/create')->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->get("/strategies/{$hospital->id}/edit")->assertForbidden();

        // กำหนดสิทธิ์ action เมนูยุทธศาสตร์ให้ผู้ดูแล รพ.
        $this->grantMenu($hospitalAdmin, 'kpi.strategy');

        // ฟอร์มสร้างเปิดได้; แก้ระดับ รพ. ได้ แต่ระดับกระทรวงถูกปฏิเสธ (ขอบเขตจากบทบาท)
        $this->actingAs($hospitalAdmin->fresh())->get('/strategies/create')->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/strategies/{$hospital->id}/edit")->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/strategies/{$ministry->id}/edit")->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->delete("/strategies/{$ministry->id}")->assertForbidden();

        // สร้างระดับกระทรวง → ถูกปฏิเสธ; สร้างระดับ รพ. → สำเร็จ
        $base = ['year' => 2569, 'code' => 'NEW', 'status' => 'enable'];
        $this->actingAs($hospitalAdmin->fresh())->post('/strategies', array_merge($base, ['level' => 'ministry', 'name' => 'SLTMARK สร้างกระทรวง']))
            ->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->post('/strategies', array_merge($base, ['level' => 'hospital', 'name' => 'SLTMARK สร้างรพ']))
            ->assertRedirect('/strategies');
        $this->assertDatabaseHas('kpi_strategies', ['name' => 'SLTMARK สร้างรพ', 'level' => 'hospital']);

        // ผู้ดูแลระบบสูงสุด → เห็นทั้งสองระดับ
        $this->actingAs($this->admin())->get('/strategies?year=2569')
            ->assertOk()->assertSee('ยุทธศาสตร์รพ')->assertSee('ยุทธศาสตร์กระทรวง');
    }

    public function test_delete_is_soft_delete(): void
    {
        $strategy = $this->makeStrategy('hospital', 'SLTDEL ยุทธศาสตร์ลบ');

        // ผู้ดูแลระบบสูงสุดลบได้ → เป็น soft delete (เหลือแถวพร้อม deleted_at)
        $this->actingAs($this->admin())->delete("/strategies/{$strategy->id}")->assertRedirect('/strategies');
        $this->assertSoftDeleted('kpi_strategies', ['id' => $strategy->id]);
    }

    public function test_strategy_menu_visible_to_indicator_admin(): void
    {
        $hospitalAdmin = $this->hospitalAdmin();
        $nav = app(PermissionService::class)->navigationFor($hospitalAdmin);

        $this->assertTrue($nav->contains(fn ($m) => $m->code === 'kpi.strategy'));
        $this->assertTrue($nav->contains(fn ($m) => $m->code === 'kpi.sub_strategy'));
    }

    public function test_sub_strategies_scoped_via_parent_strategy_level(): void
    {
        $hospitalStrategy = $this->makeStrategy('hospital', 'SLTSUB ยุทธศาสตร์รพ');
        $ministryStrategy = $this->makeStrategy('ministry', 'SLTSUB ยุทธศาสตร์กระทรวง');

        $hospitalSub = KpiSubStrategy::create(['strategy_id' => $hospitalStrategy->id, 'name' => 'SLTSUB กลยุทธ์รพ', 'status' => 'enable']);
        $ministrySub = KpiSubStrategy::create(['strategy_id' => $ministryStrategy->id, 'name' => 'SLTSUB กลยุทธ์กระทรวง', 'status' => 'enable']);

        $hospitalAdmin = $this->hospitalAdmin();
        $plain = $this->plainUser($hospitalAdmin);

        $this->actingAs($plain->fresh())->get('/sub-strategies')->assertForbidden();

        // เห็นเฉพาะกลยุทธ์ที่อยู่ใต้ยุทธศาสตร์ระดับ รพ. (ดูได้ตามบทบาท)
        $this->actingAs($hospitalAdmin->fresh())->get('/sub-strategies?year=2569')
            ->assertOk()->assertSee('กลยุทธ์รพ')->assertDontSee('กลยุทธ์กระทรวง');

        // ยังไม่ได้กำหนดสิทธิ์เมนู → ดูได้อย่างเดียว
        $this->actingAs($hospitalAdmin->fresh())->get("/sub-strategies/{$hospitalSub->id}/edit")->assertForbidden();
        $this->grantMenu($hospitalAdmin, 'kpi.sub_strategy');

        // แก้กลยุทธ์ระดับ รพ. ได้ แต่ระดับกระทรวงถูกปฏิเสธ
        $this->actingAs($hospitalAdmin->fresh())->get("/sub-strategies/{$hospitalSub->id}/edit")->assertOk();
        $this->actingAs($hospitalAdmin->fresh())->get("/sub-strategies/{$ministrySub->id}/edit")->assertForbidden();
        $this->actingAs($hospitalAdmin->fresh())->delete("/sub-strategies/{$ministrySub->id}")->assertForbidden();

        // สร้างกลยุทธ์ใต้ยุทธศาสตร์ระดับกระทรวง → ปฏิเสธ; ใต้ระดับ รพ. → สำเร็จ
        $reviewer = $plain->id;
        $this->actingAs($hospitalAdmin->fresh())->post('/sub-strategies', [
            'strategy_id' => $ministryStrategy->id, 'name' => 'SLTSUB สร้างกระทรวง', 'status' => 'enable',
            'reviewers' => [$reviewer],
        ])->assertForbidden();

        $this->actingAs($hospitalAdmin->fresh())->post('/sub-strategies', [
            'strategy_id' => $hospitalStrategy->id, 'name' => 'SLTSUB สร้างรพ', 'status' => 'enable',
            'reviewers' => [$reviewer],
        ])->assertRedirect('/sub-strategies');
        $this->assertDatabaseHas('kpi_sub_strategies', ['name' => 'SLTSUB สร้างรพ', 'strategy_id' => $hospitalStrategy->id]);
    }
}
