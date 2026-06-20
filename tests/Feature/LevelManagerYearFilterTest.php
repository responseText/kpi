<?php

namespace Tests\Feature;

use App\Models\KpiLevelManager;
use App\Models\User;
use App\Repositories\Contracts\LevelManagerRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * การค้นหาผู้รับผิดชอบระดับตามปี พ.ศ.
 * เลือกปีหนึ่ง ๆ ต้องเห็นคนของปีนั้น + คนที่ตั้งไว้ "ทุกปี" (year = null) ด้วย
 */
class LevelManagerYearFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    private function repo(): LevelManagerRepositoryInterface
    {
        return app(LevelManagerRepositoryInterface::class);
    }

    public function test_year_filter_includes_specific_year_and_all_year_entries(): void
    {
        $users = User::whereNotIn('id', [1, 2])->orderBy('id')->take(3)->get();
        [$a, $b, $c] = [$users[0], $users[1], $users[2]];

        $thisYear = KpiLevelManager::create(['level' => 'hospital', 'user_id' => $a->id, 'role' => 'responsible', 'year' => 2569]);
        $otherYear = KpiLevelManager::create(['level' => 'hospital', 'user_id' => $b->id, 'role' => 'responsible', 'year' => 2568]);
        $everyYear = KpiLevelManager::create(['level' => 'hospital', 'user_id' => $c->id, 'role' => 'definer', 'year' => null]);

        $ids = $this->repo()->allWithUser(2569)->pluck('id');

        $this->assertTrue($ids->contains($thisYear->id));    // ปีตรง → เห็น
        $this->assertTrue($ids->contains($everyYear->id));   // ทุกปี → เห็น
        $this->assertFalse($ids->contains($otherYear->id));  // คนละปี → ไม่เห็น

        // ไม่กรองปี → เห็นทั้งหมดรวมปีอื่น
        $allIds = $this->repo()->allWithUser()->pluck('id');
        $this->assertTrue($allIds->contains($otherYear->id));
    }

    public function test_available_years_lists_distinct_non_null_years(): void
    {
        $u = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        KpiLevelManager::create(['level' => 'province', 'user_id' => $u->id, 'role' => 'responsible', 'year' => 2570]);
        KpiLevelManager::create(['level' => 'province', 'user_id' => $u->id, 'role' => 'definer', 'year' => null]);

        $years = $this->repo()->availableYears();

        $this->assertTrue($years->contains(2570));
        $this->assertFalse($years->contains(null));
    }

    public function test_index_page_shows_year_filter_dropdown(): void
    {
        $u = User::whereNotIn('id', [1, 2])->orderBy('id')->firstOrFail();
        KpiLevelManager::create(['level' => 'hospital', 'user_id' => $u->id, 'role' => 'responsible', 'year' => 2569]);

        $res = $this->actingAs($this->admin())->get('/level-managers?year=2569');
        $res->assertOk();
        $res->assertSee('ปี พ.ศ.');
        $res->assertSee('แสดงผู้รับผิดชอบของปี');   // ข้อความยืนยันว่ากำลังกรอง
        $res->assertSee('2569');
    }
}
