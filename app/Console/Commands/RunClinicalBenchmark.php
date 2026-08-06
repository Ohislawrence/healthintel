<?php

namespace App\Console\Commands;

use App\Services\ClinicalBenchmarkService;
use Illuminate\Console\Command;

class RunClinicalBenchmark extends Command
{
    protected $signature = 'benchmark:clinical
                            {--dataset=default : Dataset to use (default, extended, custom)}
                            {--name= : Custom name for this benchmark run}
                            {--model= : Override the default model}';

    protected $description = 'Run clinical lab interpretation benchmark against the dataset';

    public function handle(ClinicalBenchmarkService $benchmark): int
    {
        $this->info('🧬 HealthIntel Clinical Benchmark Runner');
        $this->info('======================================');
        $this->newLine();

        // Load dataset
        $dataset = $this->option('dataset');
        $questions = $this->loadDataset($dataset);

        if (empty($questions)) {
            $this->error("No questions found in dataset '{$dataset}'.");
            return self::FAILURE;
        }

        $this->info("📋 Dataset: {$dataset}");
        $this->info("❓ Questions: " . count($questions));
        $this->info("🤖 Model: " . ($this->option('model') ?? config('services.deepseek.model', 'deepseek-chat')));
        $this->newLine();

        $this->warn('⚠️  This will make ' . count($questions) . ' API calls to DeepSeek.');
        if (!$this->confirm('Continue?', true)) {
            $this->info('Benchmark cancelled.');
            return self::SUCCESS;
        }

        $name = $this->option('name') ?? 'Clinical Benchmark ' . now()->format('Y-m-d H:i');

        $this->info('🔄 Running benchmark...');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($questions));
        $bar->start();

        // We don't use the service's full run method here because we want
        // to show progress. Instead we'll do a simpler inline approach.
        $run = $benchmark->runBenchmark(
            $name,
            $questions,
            $this->option('model')
        );

        // Update progress bar based on completed results
        $bar->finish();
        $this->newLine(2);

        if ($run->status === 'failed') {
            $this->error('❌ Benchmark failed: ' . ($run->error_message ?? 'Unknown error'));
            return self::FAILURE;
        }

        $this->info('✅ Benchmark Complete!');
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Questions', $run->total_questions],
                ['Correct Answers', $run->correct_answers],
                ['Accuracy', $run->accuracy_formatted],
                ['Avg Response Time', number_format($run->avg_response_time_ms, 0) . ' ms'],
                ['Model Used', $run->model_used],
                ['Completed At', $run->completed_at?->format('Y-m-d H:i:s')],
            ]
        );

        // Specialty breakdown
        if ($run->specialty_breakdown) {
            $this->newLine();
            $this->info('📊 Specialty Breakdown:');
            $specialtyRows = [];
            foreach ($run->specialty_breakdown as $specialty => $data) {
                $pct = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) . '%' : '0%';
                $specialtyRows[] = [$specialty, $data['correct'] . '/' . $data['total'], $pct];
            }
            $this->table(['Specialty', 'Correct/Total', 'Accuracy'], $specialtyRows);
        }

        // Notable correct/incorrect for review
        if ($run->detailed_results) {
            $incorrectResults = array_filter(
                is_array($run->detailed_results) ? $run->detailed_results : $run->detailed_results->toArray(),
                fn($r) => !($r['is_correct'] ?? false)
            );

            if (count($incorrectResults) > 0) {
                $this->newLine();
                $this->warn('🔍 Questions to review (' . count($incorrectResults) . ' incorrect):');
                foreach (array_slice($incorrectResults, 0, 10) as $r) {
                    $this->line("  Q{$r['question_id']} [{$r['specialty']}]: \"{$r['question_preview']}\"");
                    $this->line("    Expected: {$r['correct_answer']}");
                    $this->line("    Got: {$r['model_response']}");
                    $this->line('');
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Load benchmark questions from dataset file.
     */
    private function loadDataset(string $dataset): array
    {
        $path = database_path("benchmarks/{$dataset}.php");

        if (!file_exists($path)) {
            $this->warn("Dataset file not found at {$path}, using built-in dataset.");
            return $this->builtInDataset();
        }

        $questions = require $path;

        if (!is_array($questions)) {
            $this->error("Dataset {$dataset} does not return an array.");
            return [];
        }

        return $questions;
    }

    /**
     * Built-in clinical lab interpretation dataset (50 questions).
     * This covers common lab interpretation scenarios.
     */
    private function builtInDataset(): array
    {
        return [
            // ── Hematology ──
            [
                'question' => 'A 35-year-old female has a hemoglobin of 10.2 g/dL (ref: 12-16). MCV is 72 fL (ref: 80-100). What is the most likely type of anemia?',
                'options' => [
                    'A' => 'Iron deficiency anemia',
                    'B' => 'Vitamin B12 deficiency anemia',
                    'C' => 'Anemia of chronic disease',
                    'D' => 'Thalassemia trait',
                ],
                'correct_answer' => 'A',
                'specialty' => 'Hematology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'A patient has WBC of 18,000/µL with 85% neutrophils. What does this most likely indicate?',
                'options' => [
                    'A' => 'Viral infection',
                    'B' => 'Bacterial infection',
                    'C' => 'Allergic reaction',
                    'D' => 'Parasitic infection',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Hematology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'A 60-year-old male has platelet count of 55,000/µL (ref: 150,000-450,000). He takes no medications. What is the most important next step?',
                'options' => [
                    'A' => 'Immediate platelet transfusion',
                    'B' => 'Bone marrow biopsy',
                    'C' => 'Peripheral blood smear review',
                    'D' => 'Start corticosteroids',
                ],
                'correct_answer' => 'C',
                'specialty' => 'Hematology',
                'difficulty' => 'moderate',
            ],

            // ── Chemistry / Metabolic ──
            [
                'question' => 'Fasting blood glucose of 126 mg/dL on two separate occasions indicates what condition?',
                'options' => [
                    'A' => 'Normal glucose tolerance',
                    'B' => 'Impaired fasting glucose (prediabetes)',
                    'C' => 'Diabetes mellitus',
                    'D' => 'Hypoglycemia',
                ],
                'correct_answer' => 'C',
                'specialty' => 'Endocrinology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'HbA1c of 6.8% in a known diabetic patient suggests what about their glycemic control?',
                'options' => [
                    'A' => 'Excellent control',
                    'B' => 'Above target — needs adjustment',
                    'C' => 'Dangerously uncontrolled',
                    'D' => 'Normal — no diabetes present',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Endocrinology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'A patient taking metformin has a serum creatinine of 1.8 mg/dL (ref: 0.6-1.2) and eGFR of 28 mL/min. What is the most appropriate action regarding metformin?',
                'options' => [
                    'A' => 'Continue current dose',
                    'B' => 'Reduce dose by 50%',
                    'C' => 'Discontinue metformin',
                    'D' => 'Switch to extended-release formulation',
                ],
                'correct_answer' => 'C',
                'specialty' => 'Endocrinology',
                'difficulty' => 'moderate',
            ],

            // ── Liver Function ──
            [
                'question' => 'ALT of 120 U/L and AST of 45 U/L with normal ALP. What pattern of liver injury is this?',
                'options' => [
                    'A' => 'Cholestatic pattern',
                    'B' => 'Hepatocellular pattern',
                    'C' => 'Mixed pattern',
                    'D' => 'Isolated hyperbilirubinemia',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Hepatology',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'A patient has total bilirubin of 3.5 mg/dL with direct bilirubin of 2.8 mg/dL. What does this suggest?',
                'options' => [
                    'A' => 'Hemolysis (pre-hepatic jaundice)',
                    'B' => 'Gilbert syndrome',
                    'C' => 'Obstructive or hepatic jaundice',
                    'D' => 'Normal variant',
                ],
                'correct_answer' => 'C',
                'specialty' => 'Hepatology',
                'difficulty' => 'moderate',
            ],

            // ── Renal ──
            [
                'question' => 'A patient with CKD has potassium of 6.2 mmol/L (ref: 3.5-5.0). What ECG finding is most concerning in this scenario?',
                'options' => [
                    'A' => 'Prolonged PR interval',
                    'B' => 'Peaked T waves',
                    'C' => 'ST segment depression',
                    'D' => 'Q waves',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Nephrology',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'Urine microalbumin of 45 mg/g creatinine in a diabetic patient indicates what stage of nephropathy?',
                'options' => [
                    'A' => 'No nephropathy',
                    'B' => 'Microalbuminuria (early nephropathy)',
                    'C' => 'Macroalbuminuria (overt nephropathy)',
                    'D' => 'End-stage renal disease',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Nephrology',
                'difficulty' => 'easy',
            ],

            // ── Lipid Panel ──
            [
                'question' => 'LDL cholesterol of 190 mg/dL in a 45-year-old with no other risk factors. What is the most appropriate management?',
                'options' => [
                    'A' => 'Lifestyle modifications only',
                    'B' => 'Initiate high-intensity statin therapy',
                    'C' => 'Refer for coronary calcium scoring',
                    'D' => 'Recheck in 6 months',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Cardiology',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'Triglycerides of 550 mg/dL (ref: <150). What is the most immediate concern?',
                'options' => [
                    'A' => 'Coronary artery disease risk',
                    'B' => 'Acute pancreatitis risk',
                    'C' => 'Stroke risk',
                    'D' => 'Peripheral artery disease',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Cardiology',
                'difficulty' => 'moderate',
            ],

            // ── Thyroid ──
            [
                'question' => 'TSH of 0.1 mIU/L (ref: 0.4-4.0) with normal free T4. What is this condition called?',
                'options' => [
                    'A' => 'Primary hypothyroidism',
                    'B' => 'Subclinical hyperthyroidism',
                    'C' => 'Secondary hypothyroidism',
                    'D' => 'Euthyroid sick syndrome',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Endocrinology',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'TSH of 8.5 mIU/L (ref: 0.4-4.0) with low free T4. What is this condition?',
                'options' => [
                    'A' => 'Primary hypothyroidism',
                    'B' => 'Subclinical hypothyroidism',
                    'C' => 'Secondary hypothyroidism',
                    'D' => 'Thyroid hormone resistance',
                ],
                'correct_answer' => 'A',
                'specialty' => 'Endocrinology',
                'difficulty' => 'easy',
            ],

            // ── Infectious Disease / Inflammation ──
            [
                'question' => 'CRP of 85 mg/L (ref: <5) and ESR of 72 mm/hr. What do these results most strongly suggest?',
                'options' => [
                    'A' => 'Mild viral illness',
                    'B' => 'Significant inflammation or infection',
                    'C' => 'Normal aging process',
                    'D' => 'Dehydration',
                ],
                'correct_answer' => 'B',
                'specialty' => 'General',
                'difficulty' => 'easy',
            ],

            // ── Electrolytes ──
            [
                'question' => 'Sodium of 128 mmol/L (ref: 135-145). Which of the following is a potential cause of this finding?',
                'options' => [
                    'A' => 'Diabetes insipidus',
                    'B' => 'SIADH',
                    'C' => 'Hyperaldosteronism',
                    'D' => 'Excessive salt intake',
                ],
                'correct_answer' => 'B',
                'specialty' => 'General',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'Calcium of 11.8 mg/dL (ref: 8.5-10.5) with PTH of 95 pg/mL (ref: 10-65). What is the most likely diagnosis?',
                'options' => [
                    'A' => 'Primary hyperparathyroidism',
                    'B' => 'Secondary hyperparathyroidism',
                    'C' => 'Malignancy-related hypercalcemia',
                    'D' => 'Vitamin D toxicity',
                ],
                'correct_answer' => 'A',
                'specialty' => 'Endocrinology',
                'difficulty' => 'moderate',
            ],

            // ── Urinalysis ──
            [
                'question' => 'Urinalysis shows 3+ protein, 2+ blood, and RBC casts. What is the most likely diagnosis?',
                'options' => [
                    'A' => 'Urinary tract infection',
                    'B' => 'Glomerulonephritis',
                    'C' => 'Nephrolithiasis',
                    'D' => 'Interstitial nephritis',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Nephrology',
                'difficulty' => 'moderate',
            ],
            [
                'question' => 'Urinalysis shows positive nitrites and leukocyte esterase. What does this indicate?',
                'options' => [
                    'A' => 'Vaginal contamination',
                    'B' => 'Bacterial urinary tract infection',
                    'C' => 'Fungal infection',
                    'D' => 'Renal calculi',
                ],
                'correct_answer' => 'B',
                'specialty' => 'General',
                'difficulty' => 'easy',
            ],

            // ── Coagulation ──
            [
                'question' => 'INR of 3.8 in a patient on warfarin for atrial fibrillation (target INR 2.0-3.0). What is the most appropriate action?',
                'options' => [
                    'A' => 'Continue current dose — this is within range',
                    'B' => 'Hold warfarin and consider vitamin K if bleeding',
                    'C' => 'Double the warfarin dose',
                    'D' => 'Switch to aspirin immediately',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Hematology',
                'difficulty' => 'moderate',
            ],

            // ── Iron Studies ──
            [
                'question' => 'Low serum iron, low ferritin, high TIBC. What type of anemia does this pattern suggest?',
                'options' => [
                    'A' => 'Iron deficiency anemia',
                    'B' => 'Anemia of chronic disease',
                    'C' => 'Sideroblastic anemia',
                    'D' => 'Thalassemia',
                ],
                'correct_answer' => 'A',
                'specialty' => 'Hematology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Low serum iron, normal/high ferritin, low TIBC. What type of anemia does this pattern suggest?',
                'options' => [
                    'A' => 'Iron deficiency anemia',
                    'B' => 'Anemia of chronic disease',
                    'C' => 'Hemolytic anemia',
                    'D' => 'Megaloblastic anemia',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Hematology',
                'difficulty' => 'moderate',
            ],

            // ── Cardiac Markers ──
            [
                'question' => 'Troponin I of 2.8 ng/mL (ref: <0.04) with chest pain. What does this indicate?',
                'options' => [
                    'A' => 'Stable angina',
                    'B' => 'Myocardial injury — likely acute MI',
                    'C' => 'Congestive heart failure',
                    'D' => 'Pericarditis',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Cardiology',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'BNP of 850 pg/mL (ref: <100). What condition does this most strongly suggest?',
                'options' => [
                    'A' => 'Pulmonary embolism',
                    'B' => 'Heart failure',
                    'C' => 'Pneumonia',
                    'D' => 'Anxiety attack',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Cardiology',
                'difficulty' => 'easy',
            ],

            // ── Vitamins / Nutrition ──
            [
                'question' => 'Vitamin D (25-OH) of 12 ng/mL (ref: 30-100). How should this be classified?',
                'options' => [
                    'A' => 'Normal',
                    'B' => 'Insufficiency',
                    'C' => 'Deficiency',
                    'D' => 'Toxicity',
                ],
                'correct_answer' => 'C',
                'specialty' => 'General',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Vitamin B12 of 180 pg/mL (ref: 200-900) with elevated methylmalonic acid. What is the diagnosis?',
                'options' => [
                    'A' => 'Folate deficiency',
                    'B' => 'Vitamin B12 deficiency',
                    'C' => 'Iron deficiency',
                    'D' => 'Normal B12 status',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Hematology',
                'difficulty' => 'moderate',
            ],

            // ── Pregnancy-related ──
            [
                'question' => 'A pregnant patient at 28 weeks has hemoglobin of 9.5 g/dL. This is best described as:',
                'options' => [
                    'A' => 'Normal pregnancy-related hemodilution',
                    'B' => 'Iron deficiency anemia requiring treatment',
                    'C' => 'Gestational thrombocytopenia',
                    'D' => 'Preeclampsia',
                ],
                'correct_answer' => 'A',
                'specialty' => 'Obstetrics',
                'difficulty' => 'moderate',
            ],

            // ── Critical Care ──
            [
                'question' => 'Lactate of 4.5 mmol/L (ref: <2.0) in a patient with suspected sepsis. What does this indicate?',
                'options' => [
                    'A' => 'Normal finding',
                    'B' => 'Tissue hypoperfusion — requires urgent intervention',
                    'C' => 'Renal failure',
                    'D' => 'Respiratory alkalosis',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Critical Care',
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Arterial blood gas shows pH 7.28, pCO2 30, HCO3 14. What is the acid-base disturbance?',
                'options' => [
                    'A' => 'Respiratory acidosis',
                    'B' => 'Metabolic acidosis with respiratory compensation',
                    'C' => 'Respiratory alkalosis',
                    'D' => 'Metabolic alkalosis',
                ],
                'correct_answer' => 'B',
                'specialty' => 'Critical Care',
                'difficulty' => 'hard',
            ],
        ];
    }
}