<?php

namespace Tests\Unit;

use App\Models\CustomerDeviceStage;
use App\Services\FilterLifecycleEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterLifecycleEngineTest extends TestCase
{
    use RefreshDatabase;

    protected FilterLifecycleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FilterLifecycleEngine();
    }

    public function test_calendar_one_month_addition_in_leap_year_returns_feb_29(): void
    {
        // 2024 is a leap year. Jan 31 + 1 month should be Feb 29 (no overflow into March)
        $startDate = Carbon::create(2024, 1, 31);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 30, 1);

        $this->assertEquals('2024-02-29', $dueDate->toDateString());
    }

    public function test_calendar_one_month_addition_in_non_leap_year_returns_feb_28(): void
    {
        // 2023 is not a leap year. Jan 31 + 1 month should be Feb 28
        $startDate = Carbon::create(2023, 1, 31);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 30, 1);

        $this->assertEquals('2023-02-28', $dueDate->toDateString());
    }

    public function test_calendar_three_months_addition_from_november_30(): void
    {
        // Nov 30, 2023 + 3 months -> Feb 29, 2024 (leap year)
        $startDate = Carbon::create(2023, 11, 30);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 90, 3);

        $this->assertEquals('2024-02-29', $dueDate->toDateString());
    }

    public function test_calendar_six_months_addition(): void
    {
        // Aug 31, 2024 + 6 months -> Feb 28, 2025
        $startDate = Carbon::create(2024, 8, 31);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 180, 6);

        $this->assertEquals('2025-02-28', $dueDate->toDateString());
    }

    public function test_calendar_one_year_addition_from_leap_day(): void
    {
        // Feb 29, 2024 + 12 months -> Feb 28, 2025
        $startDate = Carbon::create(2024, 2, 29);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 365, 12);

        $this->assertEquals('2025-02-28', $dueDate->toDateString());
    }

    public function test_calendar_two_years_membrane_interval(): void
    {
        $startDate = Carbon::create(2024, 5, 15);
        $dueDate = $this->engine->calculateNextDueDate($startDate, 720, 24);

        $this->assertEquals('2026-05-15', $dueDate->toDateString());
    }

    public function test_stage_status_evaluation_logic(): void
    {
        $today = Carbon::create(2026, 10, 29);

        // Stage 1: Due in 45 days -> not_due
        $stage1 = new CustomerDeviceStage([
            'next_due_at' => '2026-12-13',
            'status' => 'not_due',
        ]);
        $this->assertEquals('not_due', $this->engine->refreshStageStatus($stage1, $today));

        // Stage 2: Due in 7 days -> upcoming (within 14 days window)
        $stage2 = new CustomerDeviceStage([
            'next_due_at' => '2026-11-05',
            'status' => 'not_due',
        ]);
        $this->assertEquals('upcoming', $this->engine->refreshStageStatus($stage2, $today));

        // Stage 3: Due today -> due_today
        $stage3 = new CustomerDeviceStage([
            'next_due_at' => '2026-10-29',
            'status' => 'not_due',
        ]);
        $this->assertEquals('due_today', $this->engine->refreshStageStatus($stage3, $today));

        // Stage 4: Due 5 days ago -> overdue
        $stage4 = new CustomerDeviceStage([
            'next_due_at' => '2026-10-24',
            'status' => 'not_due',
        ]);
        $this->assertEquals('overdue', $this->engine->refreshStageStatus($stage4, $today));

        // Stage 5: Postponed until next month -> postponed
        $stage5 = new CustomerDeviceStage([
            'next_due_at' => '2026-10-24',
            'status' => 'postponed',
            'postponed_until' => '2026-11-20',
        ]);
        $this->assertEquals('postponed', $this->engine->refreshStageStatus($stage5, $today));

        // Stage 6: Exempted -> exempted
        $stage6 = new CustomerDeviceStage([
            'next_due_at' => '2026-10-24',
            'status' => 'exempted',
        ]);
        $this->assertEquals('exempted', $this->engine->refreshStageStatus($stage6, $today));
    }
}
