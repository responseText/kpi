<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnMenu;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * การเข้าถึงเมนู "บันทึกผลงาน":
 * - ผู้รับผิดชอบตัวชี้วัด (owner) เห็น/ใช้เมนูได้แม้ผู้ดูแลยังไม่กำหนดสิทธิ์เมนูให้
 * - ผู้ที่ยังไม่มีสิทธิ์ → เจอข้อความให้ติดต่องานสารสนเทศ (ไม่ใช่ 403 เปล่า ๆ)
 */
class ResultMenuAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function nav(User $user)
    {
        return app(PermissionService::class)->navigationFor($user->fresh());
    }

    private function hasResultMenu(User $user): bool
    {
        return $this->nav($user)->contains(fn (Menu $m) => $m->code === 'kpi.result');
    }

    /** ผู้ใช้ทั่วไปที่ "สะอาด": ไม่เป็นเจ้าของตัวชี้วัด และไม่มีสิทธิ์เมนูบันทึกผล (เคลียร์ใน transaction) */
    private function cleanUser(): User
    {
        $user = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();

        DB::table('kpi_indicator_owners')->where('user_id', $user->id)->delete();
        $resultMenuId = Menu::where('code', 'kpi.result')->value('id');
        UserOnMenu::where('user_id', $user->id)->where('menu_id', $resultMenuId)->delete();

        return $user->fresh();
    }

    private function makeIndicator(): KpiIndicator
    {
        $strategy = KpiStrategy::create(['year' => 2569, 'code' => 'RA', 'name' => 'ยุทธศาสตร์สิทธิ์เมนูผล', 'status' => 'enable']);
        $sub = KpiSubStrategy::create(['strategy_id' => $strategy->id, 'name' => 'กลยุทธ์สิทธิ์เมนูผล', 'status' => 'enable']);

        return KpiIndicator::create([
            'sub_strategy_id' => $sub->id, 'level' => 'hospital', 'name' => 'ตัวชี้วัดสิทธิ์เมนูผล',
            'year_type' => 'buddhist', 'year' => 2569, 'period_type' => 'annual', 'status' => 'enable',
        ]);
    }

    public function test_indicator_owner_sees_results_menu_without_explicit_grant(): void
    {
        $owner = $this->cleanUser();

        // ก่อนเป็นผู้รับผิดชอบ → ยังไม่เห็นเมนู
        $this->assertFalse($this->hasResultMenu($owner));

        $ind = $this->makeIndicator();
        $ind->owners()->attach($owner->id, ['is_primary' => true]);

        // เป็นผู้รับผิดชอบแล้ว → เห็นเมนู + เข้าหน้าเมนูและบันทึกผลของตัวเองได้
        $this->assertTrue($this->hasResultMenu($owner));
        $this->actingAs($owner->fresh())->get('/results')->assertOk();
        $this->actingAs($owner->fresh())->get("/results/{$ind->id}/edit")->assertOk();
    }

    public function test_user_without_result_access_sees_contact_message(): void
    {
        $user = $this->cleanUser();

        $res = $this->actingAs($user)->get('/results');
        $res->assertForbidden();
        $res->assertSee('งานสารสนเทศทางการแพทย์');   // ข้อความแนะนำให้ติดต่อ แทน 403 เปล่า
    }

    public function test_other_menu_denial_shows_same_contact_message(): void
    {
        $user = $this->cleanUser();

        // เมนูอื่น (ผู้รับผิดชอบระดับ) ที่ผู้ใช้ไม่มีสิทธิ์ → ข้อความเดียวกัน
        $res = $this->actingAs($user)->get('/level-managers');
        $res->assertForbidden();
        $res->assertSee('งานสารสนเทศทางการแพทย์');
    }

    public function test_super_admin_is_never_blocked(): void
    {
        $admin = User::findOrFail(1);
        $this->actingAs($admin)->get('/results')->assertOk();
        $this->actingAs($admin)->get('/level-managers')->assertOk();
    }
}
