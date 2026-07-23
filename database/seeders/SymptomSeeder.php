<?php

namespace Database\Seeders;

use App\Models\Symptom;
use App\Models\TestPanel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SymptomSeeder extends Seeder
{
    private array $symptoms = [
        // General & Systemic
        ['name' => 'Fatigue', 'slug' => 'fatigue', 'description' => 'Persistent tiredness or lack of energy', 'category' => 'General', 'panels' => ['full-blood-count', 'thyroid-function-test', 'iron-studies', 'diabetic-panel']],
        ['name' => 'Fever', 'slug' => 'fever', 'description' => 'Elevated body temperature above 37.5C', 'category' => 'General', 'panels' => ['full-blood-count']],
        ['name' => 'Unexplained Weight Loss', 'slug' => 'weight-loss', 'description' => 'Losing weight without trying', 'category' => 'General', 'panels' => ['full-blood-count', 'thyroid-function-test', 'diabetic-panel']],
        ['name' => 'Unexplained Weight Gain', 'slug' => 'weight-gain', 'description' => 'Gaining weight without changes to diet or activity', 'category' => 'General', 'panels' => ['thyroid-function-test', 'diabetic-panel']],
        ['name' => 'Night Sweats', 'slug' => 'night-sweats', 'description' => 'Excessive sweating during sleep', 'category' => 'General', 'panels' => ['full-blood-count', 'thyroid-function-test']],
        // Cardiorespiratory
        ['name' => 'Chest Pain', 'slug' => 'chest-pain', 'description' => 'Pain or discomfort in the chest area', 'category' => 'Cardiorespiratory', 'panels' => ['lipid-profile', 'full-blood-count']],
        ['name' => 'Shortness of Breath', 'slug' => 'shortness-of-breath', 'description' => 'Difficulty breathing or feeling winded', 'category' => 'Cardiorespiratory', 'panels' => ['full-blood-count', 'iron-studies', 'thyroid-function-test']],
        ['name' => 'Heart Palpitations', 'slug' => 'palpitations', 'description' => 'Racing, pounding, or irregular heartbeat sensation', 'category' => 'Cardiorespiratory', 'panels' => ['thyroid-function-test', 'full-blood-count', 'iron-studies']],
        ['name' => 'High Blood Pressure', 'slug' => 'high-blood-pressure', 'description' => 'Consistently elevated blood pressure readings', 'category' => 'Cardiorespiratory', 'panels' => ['lipid-profile', 'renal-function-test', 'diabetic-panel']],
        // Gastrointestinal
        ['name' => 'Abdominal Pain', 'slug' => 'abdominal-pain', 'description' => 'Pain or cramping in the stomach area', 'category' => 'Gastrointestinal', 'panels' => ['liver-function-test', 'full-blood-count', 'renal-function-test']],
        ['name' => 'Nausea', 'slug' => 'nausea', 'description' => 'Feeling of sickness with an urge to vomit', 'category' => 'Gastrointestinal', 'panels' => ['liver-function-test', 'renal-function-test']],
        ['name' => 'Diarrhea', 'slug' => 'diarrhea', 'description' => 'Loose, watery stools that occur more frequently than usual', 'category' => 'Gastrointestinal', 'panels' => ['renal-function-test', 'full-blood-count']],
        ['name' => 'Constipation', 'slug' => 'constipation', 'description' => 'Difficult or infrequent bowel movements', 'category' => 'Gastrointestinal', 'panels' => ['thyroid-function-test', 'renal-function-test']],
        ['name' => 'Jaundice', 'slug' => 'jaundice', 'description' => 'Yellowing of the skin and whites of the eyes', 'category' => 'Gastrointestinal', 'panels' => ['liver-function-test', 'full-blood-count']],
        // Neurological
        ['name' => 'Headache', 'slug' => 'headache', 'description' => 'Pain or discomfort in the head or neck region', 'category' => 'Neurological', 'panels' => ['full-blood-count', 'iron-studies', 'thyroid-function-test']],
        ['name' => 'Dizziness', 'slug' => 'dizziness', 'description' => 'Feeling lightheaded, unsteady, or faint', 'category' => 'Neurological', 'panels' => ['full-blood-count', 'iron-studies', 'diabetic-panel']],
        ['name' => 'Numbness or Tingling', 'slug' => 'numbness-tingling', 'description' => 'Pins-and-needles sensation, especially in hands or feet', 'category' => 'Neurological', 'panels' => ['diabetic-panel', 'iron-studies', 'thyroid-function-test']],
        ['name' => 'Memory Problems', 'slug' => 'memory-problems', 'description' => 'Difficulty remembering or concentrating', 'category' => 'Neurological', 'panels' => ['thyroid-function-test', 'iron-studies']],
        // Musculoskeletal
        ['name' => 'Joint Pain', 'slug' => 'joint-pain', 'description' => 'Pain, swelling, or stiffness in joints', 'category' => 'Musculoskeletal', 'panels' => ['full-blood-count', 'renal-function-test']],
        ['name' => 'Muscle Pain', 'slug' => 'muscle-pain', 'description' => 'Generalized muscle aches and soreness', 'category' => 'Musculoskeletal', 'panels' => ['renal-function-test', 'thyroid-function-test', 'iron-studies']],
        ['name' => 'Back Pain', 'slug' => 'back-pain', 'description' => 'Pain in the upper, middle, or lower back', 'category' => 'Musculoskeletal', 'panels' => ['renal-function-test', 'full-blood-count']],
        // Endocrine/Metabolic
        ['name' => 'Frequent Urination', 'slug' => 'frequent-urination', 'description' => 'Needing to urinate more often than usual', 'category' => 'Endocrine', 'panels' => ['diabetic-panel', 'renal-function-test']],
        ['name' => 'Excessive Thirst', 'slug' => 'excessive-thirst', 'description' => 'Feeling unusually thirsty all the time', 'category' => 'Endocrine', 'panels' => ['diabetic-panel', 'renal-function-test']],
        ['name' => 'Cold Intolerance', 'slug' => 'cold-intolerance', 'description' => 'Feeling unusually cold when others are comfortable', 'category' => 'Endocrine', 'panels' => ['thyroid-function-test', 'iron-studies']],
        ['name' => 'Heat Intolerance', 'slug' => 'heat-intolerance', 'description' => 'Feeling excessively hot or sweating more than usual', 'category' => 'Endocrine', 'panels' => ['thyroid-function-test']],
        ['name' => 'Hair Loss', 'slug' => 'hair-loss', 'description' => 'Noticeable thinning or loss of hair', 'category' => 'Endocrine', 'panels' => ['thyroid-function-test', 'iron-studies']],
        // Hematologic
        ['name' => 'Pale Skin', 'slug' => 'pale-skin', 'description' => 'Unusually pale or washed-out skin colour', 'category' => 'Hematologic', 'panels' => ['full-blood-count', 'iron-studies']],
        ['name' => 'Easy Bruising', 'slug' => 'easy-bruising', 'description' => 'Bruising more easily than normal', 'category' => 'Hematologic', 'panels' => ['full-blood-count', 'liver-function-test']],
        ['name' => 'Bleeding Gums', 'slug' => 'bleeding-gums', 'description' => 'Gums that bleed easily when brushing', 'category' => 'Hematologic', 'panels' => ['full-blood-count']],
        // Womens Health
        ['name' => 'Menstrual Irregularities', 'slug' => 'menstrual-irregularities', 'description' => 'Irregular, missed, or heavy periods', 'category' => 'Womens Health', 'panels' => ['full-blood-count', 'iron-studies', 'thyroid-function-test']],
        ['name' => 'Pelvic Pain', 'slug' => 'pelvic-pain', 'description' => 'Pain in the lowest part of the abdomen', 'category' => 'Womens Health', 'panels' => ['full-blood-count', 'renal-function-test']],
        // Renal/Urinary
        ['name' => 'Swollen Ankles or Feet', 'slug' => 'swollen-ankles', 'description' => 'Fluid retention causing swelling in lower extremities', 'category' => 'Renal', 'panels' => ['renal-function-test', 'liver-function-test', 'full-blood-count']],
        ['name' => 'Painful Urination', 'slug' => 'painful-urination', 'description' => 'Burning or discomfort when urinating', 'category' => 'Renal', 'panels' => ['renal-function-test']],
        ['name' => 'Blood in Urine', 'slug' => 'blood-in-urine', 'description' => 'Red or pink discolouration of urine', 'category' => 'Renal', 'panels' => ['renal-function-test', 'full-blood-count']],
    ];

    public function run(): void
    {
        foreach ($this->symptoms as $idx => $data) {
            $panels = $data['panels'];
            unset($data['panels']);
            $data['sort_order'] = $idx + 1;

            $symptom = Symptom::firstOrCreate(['slug' => $data['slug']], $data);

            $panelIds = TestPanel::whereIn('slug', $panels)->pluck('id', 'slug');
            $rank = count($panels);
            foreach ($panels as $slug) {
                if (isset($panelIds[$slug])) {
                    DB::table('symptom_test_panels')->insertOrIgnore([
                        'symptom_id' => $symptom->id,
                        'test_panel_id' => $panelIds[$slug],
                        'relevance_score' => $rank,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rank--;
                }
            }
        }
    }
}
