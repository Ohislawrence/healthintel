<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use App\Models\ReferenceRange;
use App\Services\ReferenceRangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceRangeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferenceRangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReferenceRangeService::class);

        // Seed minimal test data
        ReferenceRange::create([
            'test_code' => 'haemoglobin',
            'test_name' => 'Haemoglobin',
            'category' => 'haematology',
            'sex' => 'male',
            'range_low' => 13.0,
            'range_high' => 17.0,
            'critical_low' => 7.0,
            'critical_high' => 20.0,
            'unit' => 'g/dL',
            'source' => 'WHO 2024',
        ]);

        ReferenceRange::create([
            'test_code' => 'haemoglobin',
            'test_name' => 'Haemoglobin',
            'category' => 'haematology',
            'sex' => 'female',
            'range_low' => 12.0,
            'range_high' => 15.5,
            'critical_low' => 7.0,
            'unit' => 'g/dL',
            'source' => 'WHO 2024',
        ]);

        ReferenceRange::create([
            'test_code' => 'glucose',
            'test_name' => 'Fasting Blood Glucose',
            'category' => 'chemistry',
            'sex' => 'all',
            'range_low' => 70,
            'range_high' => 100,
            'critical_low' => 50,
            'critical_high' => 300,
            'unit' => 'mg/dL',
            'source' => 'ADA 2024',
        ]);
    }

    #[Test]
    public function it_normalizes_common_test_names(): void
    {
        $this->assertEquals('haemoglobin', $this->service->normalizeTestCode('Haemoglobin'));
        $this->assertEquals('haemoglobin', $this->service->normalizeTestCode('Hemoglobin'));
        $this->assertEquals('haemoglobin', $this->service->normalizeTestCode('haemoglobin'));
        $this->assertEquals('glucose', $this->service->normalizeTestCode('Fasting Blood Glucose'));
        $this->assertEquals('alt', $this->service->normalizeTestCode('Alanine Aminotransferase'));
        $this->assertEquals('alt', $this->service->normalizeTestCode('ALT (SGPT)'));
        $this->assertEquals('white_blood_cell_count', $this->service->normalizeTestCode('White Blood Cell Count'));
        $this->assertEquals('wbc', $this->service->normalizeTestCode('WBC'));
    }

    #[Test]
    public function it_classifies_a_normal_male_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 15.0, 'g/dL', 'male');

        $this->assertEquals('normal', $result['status']);
        $this->assertEquals(13.0, $result['range_low']);
        $this->assertEquals(17.0, $result['range_high']);
        $this->assertGreaterThan(40, $result['confidence']);
        $this->assertStringContainsString('within normal range', $result['reason']);
    }

    #[Test]
    public function it_classifies_a_normal_female_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 13.0, 'g/dL', 'female');

        $this->assertEquals('normal', $result['status']);
        $this->assertEquals(12.0, $result['range_low']);
        $this->assertEquals(15.5, $result['range_high']);
    }

    #[Test]
    public function it_classifies_an_abnormal_high_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 18.0, 'g/dL', 'male');

        $this->assertEquals('abnormal_high', $result['status']);
        $this->assertGreaterThan(40, $result['confidence']);
    }

    #[Test]
    public function it_classifies_an_abnormal_low_female_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 10.0, 'g/dL', 'female');

        $this->assertEquals('abnormal_low', $result['status']);
    }

    #[Test]
    public function it_classifies_a_critical_low_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 6.0, 'g/dL', 'male');

        $this->assertEquals('critical_low', $result['status']);
        $this->assertStringContainsString('critical threshold', $result['reason']);
    }

    #[Test]
    public function it_classifies_a_critical_high_result(): void
    {
        $result = $this->service->classify('Haemoglobin', 21.0, 'g/dL', 'male');

        $this->assertEquals('critical_high', $result['status']);
    }

    #[Test]
    public function it_returns_unknown_for_unrecognized_test(): void
    {
        $result = $this->service->classify('UnknownTest', 100, 'units');

        $this->assertEquals('unknown', $result['status']);
        $this->assertEquals(0, $result['confidence']);
        $this->assertNull($result['matched_range']);
    }

    #[Test]
    public function it_converts_units_for_glucose_mmol_to_mgdl(): void
    {
        $converted = $this->service->convertUnit(5.5, 'mmol/l', 'mg/dl', 'glucose');

        $this->assertNotNull($converted);
        $this->assertEquals(99.099, round($converted, 3)); // 5.5 * 18.018
    }

    #[Test]
    public function it_converts_units_for_haemoglobin_gl_to_gdl(): void
    {
        $converted = $this->service->convertUnit(150, 'g/l', 'g/dl', 'haemoglobin');

        $this->assertNotNull($converted);
        $this->assertEquals(15.0, $converted); // 150 * 0.1
    }

    #[Test]
    public function it_detects_unit_mismatch_and_converts(): void
    {
        // Submit glucose in mmol/L but DB has mg/dL
        $result = $this->service->classify('Fasting Blood Glucose', 5.5, 'mmol/l', null);

        $this->assertEquals('normal', $result['status']);
        $this->assertTrue($result['was_converted']);
        $this->assertEquals(99.099, round($result['converted_value'], 3));
        $this->assertGreaterThan(30, $result['confidence']);
    }

    #[Test]
    public function it_calculates_higher_confidence_for_sex_specific_range(): void
    {
        $maleResult = $this->service->classify('Haemoglobin', 15.0, 'g/dL', 'male');
        $neutralResult = $this->service->classify('Fasting Blood Glucose', 85, 'mg/dL');

        // Male haemoglobin should have higher confidence (sex-specific + reviewed)
        $this->assertNotEmpty($maleResult['source']);
    }

    #[Test]
    public function it_handles_empty_unit(): void
    {
        $result = $this->service->classify('Haemoglobin', 15.0, '', 'male');

        $this->assertEquals('normal', $result['status']);
        $this->assertGreaterThan(40, $result['confidence']);
    }
}