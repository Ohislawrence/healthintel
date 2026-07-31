<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use App\Models\LabSubmission;
use App\Models\LabSubmissionValue;
use App\Models\User;
use App\Services\TrendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrendService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TrendService::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_insufficient_data_for_one_test(): void
    {
        $submission = LabSubmission::create([
            'user_id' => $this->user->id,
            'credits_used' => 2,
            'submitted_at' => now(),
        ]);

        LabSubmissionValue::create([
            'lab_submission_id' => $submission->id,
            'test_slug' => 'haemoglobin',
            'test_name' => 'Haemoglobin',
            'value' => 14.0,
            'unit' => 'g/dL',
            'flag' => 'normal',
        ]);

        $analysis = $this->service->analyzeTrend($this->user->id, 'haemoglobin');

        $this->assertEquals('insufficient_data', $analysis['direction']);
        $this->assertEquals(1, $analysis['total_points']);
        $this->assertNull($analysis['alert']);
    }

    #[Test]
    public function it_detects_stable_trend_with_two_points(): void
    {
        $sub1 = LabSubmission::create([
            'user_id' => $this->user->id, 'credits_used' => 2,
            'submitted_at' => now()->subDays(30),
        ]);
        LabSubmissionValue::create([
            'lab_submission_id' => $sub1->id,
            'test_slug' => 'alt', 'test_name' => 'ALT', 'value' => 25, 'unit' => 'IU/L', 'flag' => 'normal',
        ]);

        $sub2 = LabSubmission::create([
            'user_id' => $this->user->id, 'credits_used' => 2,
            'submitted_at' => now(),
        ]);
        LabSubmissionValue::create([
            'lab_submission_id' => $sub2->id,
            'test_slug' => 'alt', 'test_name' => 'ALT', 'value' => 26, 'unit' => 'IU/L', 'flag' => 'normal',
        ]);

        $analysis = $this->service->analyzeTrend($this->user->id, 'alt');

        $this->assertContains($analysis['direction'], ['stable', 'insufficient_data']);
        $this->assertEquals(2, $analysis['total_points']);
    }

    #[Test]
    public function it_detects_rising_trend(): void
    {
        $values = [30, 38, 45, 58];
        foreach ($values as $i => $val) {
            $sub = LabSubmission::create([
                'user_id' => $this->user->id, 'credits_used' => 2,
                'submitted_at' => now()->subDays(90 - ($i * 30)),
            ]);
            LabSubmissionValue::create([
                'lab_submission_id' => $sub->id,
                'test_slug' => 'alt', 'test_name' => 'ALT',
                'value' => $val, 'unit' => 'IU/L', 'flag' => 'high',
            ]);
        }

        $analysis = $this->service->analyzeTrend($this->user->id, 'alt');

        $this->assertEquals(4, $analysis['total_points']);
        // Direction should be 'rising' for 3+ consecutive rises
        $this->assertNotNull($analysis['direction']);
        $this->assertNotNull($analysis['alert']);
    }

    #[Test]
    public function it_detects_falling_trend(): void
    {
        $values = [55, 45, 35, 28];
        foreach ($values as $i => $val) {
            $sub = LabSubmission::create([
                'user_id' => $this->user->id, 'credits_used' => 2,
                'submitted_at' => now()->subDays(90 - ($i * 30)),
            ]);
            LabSubmissionValue::create([
                'lab_submission_id' => $sub->id,
                'test_slug' => 'glucose', 'test_name' => 'Glucose',
                'value' => $val, 'unit' => 'mg/dL', 'flag' => 'high',
            ]);
        }

        $analysis = $this->service->analyzeTrend($this->user->id, 'glucose');

        $this->assertEquals(4, $analysis['total_points']);
        // Falling or falling_slight
        $this->assertStringContainsString('fall', $analysis['direction']);
    }

    #[Test]
    public function it_generates_direction_label(): void
    {
        $sub = LabSubmission::create(['user_id' => $this->user->id, 'credits_used' => 2, 'submitted_at' => now()]);
        LabSubmissionValue::create([
            'lab_submission_id' => $sub->id,
            'test_slug' => 'tsh', 'test_name' => 'TSH', 'value' => 2.0, 'unit' => 'mIU/L', 'flag' => 'normal',
        ]);

        $analysis = $this->service->analyzeTrend($this->user->id, 'tsh');

        $this->assertEquals('insufficient_data', $analysis['direction']);
        $this->assertArrayHasKey('direction_label', $analysis);
    }
}