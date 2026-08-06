<?php

namespace App\Services;

use App\Models\BenchmarkRun;

class ClinicalBenchmarkService
{
    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    /**
     * Run a benchmark against the clinical dataset.
     */
    public function runBenchmark(string $name, array $questions, ?string $modelOverride = null): BenchmarkRun
    {
        $run = BenchmarkRun::create([
            'name' => $name,
            'dataset_version' => 'v1',
            'total_questions' => count($questions),
            'status' => 'running',
            'model_used' => $modelOverride ?? config('services.deepseek.model', 'deepseek-chat'),
            'started_at' => now(),
        ]);

        try {
            $detailedResults = [];
            $correctAnswers = 0;
            $specialtyBreakdown = [];
            $difficultyBreakdown = [];
            $totalResponseTime = 0;
            $responseCount = 0;

            foreach ($questions as $idx => $question) {
                $startTime = microtime(true);

                // Build a focused clinical interpretation prompt
                $prompt = $this->buildQuestionPrompt($question);

                // Ask DeepSeek with a neutral benchmark prompt — NOT the clinical interpreter prompt
                $response = $this->deepSeek->ask(
                    $prompt,
                    350,
                    0.1,
                    "You are answering clinical lab interpretation multiple-choice questions for a benchmark. Read the question carefully. Start your response with 'ANSWER: ' followed by ONLY the correct answer letter (A, B, C, or D) or text. Do NOT explain. Do NOT use markdown. Do NOT add commentary."
                );

                $responseTime = (microtime(true) - $startTime) * 1000; // in ms
                $totalResponseTime += $responseTime;
                $responseCount++;

                $isCorrect = false;
                $extractedAnswer = null;
                $correctAnswer = $question['correct_answer'] ?? $question['expected_answer'] ?? '';

                if ($response) {
                    $extractedAnswer = $this->extractAnswer($response, $question);
                    $isCorrect = $this->scoreAnswer($extractedAnswer, $correctAnswer, $question);
                }

                if ($isCorrect) {
                    $correctAnswers++;
                }

                // Specialty tracking
                $specialty = $question['specialty'] ?? 'General';
                if (!isset($specialtyBreakdown[$specialty])) {
                    $specialtyBreakdown[$specialty] = ['total' => 0, 'correct' => 0];
                }
                $specialtyBreakdown[$specialty]['total']++;
                if ($isCorrect) {
                    $specialtyBreakdown[$specialty]['correct']++;
                }

                // Difficulty tracking
                $difficulty = $question['difficulty'] ?? 'moderate';
                if (!isset($difficultyBreakdown[$difficulty])) {
                    $difficultyBreakdown[$difficulty] = ['total' => 0, 'correct' => 0];
                }
                $difficultyBreakdown[$difficulty]['total']++;
                if ($isCorrect) {
                    $difficultyBreakdown[$difficulty]['correct']++;
                }

                $detailedResults[] = [
                    'question_id' => $idx + 1,
                    'specialty' => $specialty,
                    'difficulty' => $difficulty,
                    'question_preview' => substr($question['question'] ?? $question['prompt'] ?? '', 0, 120),
                    'correct_answer' => $correctAnswer,
                    'model_response' => $extractedAnswer,
                    'full_response' => $response,
                    'is_correct' => $isCorrect,
                    'response_time_ms' => round($responseTime, 2),
                ];
            }

            $accuracy = $responseCount > 0
                ? round(($correctAnswers / $responseCount) * 100, 2)
                : 0;

            $avgResponseTime = $responseCount > 0
                ? round($totalResponseTime / $responseCount, 2)
                : 0;

            $run->update([
                'correct_answers' => $correctAnswers,
                'accuracy' => $accuracy,
                'avg_response_time_ms' => $avgResponseTime,
                'specialty_breakdown' => $specialtyBreakdown,
                'difficulty_breakdown' => $difficultyBreakdown,
                'detailed_results' => $detailedResults,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $run->fresh();
    }

    /**
     * Get the latest completed benchmark run for display.
     */
    public function getLatestBenchmark(): ?BenchmarkRun
    {
        return BenchmarkRun::where('status', 'completed')
            ->latest('completed_at')
            ->first();
    }

    /**
     * Build a prompt for a single benchmark question.
     */
    private function buildQuestionPrompt(array $question): string
    {
        $lines = [
            "You are a clinical lab result interpreter. Answer the following medical question based on standard clinical guidelines.",
            "",
            "QUESTION:",
            $question['question'] ?? $question['prompt'] ?? $question['text'] ?? '',
        ];

        if (!empty($question['options'])) {
            $lines[] = '';
            $lines[] = "OPTIONS:";
            foreach ($question['options'] as $label => $option) {
                $lines[] = "$label. $option";
            }
        }

        if (!empty($question['context'])) {
            $lines[] = '';
            $lines[] = "CONTEXT:";
            $lines[] = $question['context'];
        }

        $lines[] = '';
        $lines[] = 'IMPORTANT: Respond with ONLY the answer. Start your response with "ANSWER:" followed by the correct answer. Keep your response concise.';

        return implode("\n", $lines);
    }

    /**
     * Extract the answer from the LLM response.
     */
    private function extractAnswer(string $response, array $question): ?string
    {
        // Try to find "ANSWER:" prefix
        if (preg_match('/ANSWER:\s*(.+?)(?:\n|$)/is', $response, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: return first non-empty line of response
        $lines = array_filter(explode("\n", $response), fn($l) => trim($l) !== '');
        $firstLine = reset($lines);
        return $firstLine ? trim($firstLine) : null;
    }

    /**
     * Score an answer against the expected correct answer.
     */
    private function scoreAnswer(?string $modelAnswer, string $correctAnswer, array $question): bool
    {
        if ($modelAnswer === null) {
            return false;
        }

        // Normalize both answers for comparison
        $normalizedModel = strtolower(trim($modelAnswer));
        $normalizedCorrect = strtolower(trim($correctAnswer));

        // Exact match
        if ($normalizedModel === $normalizedCorrect) {
            return true;
        }

        // Option-letter match (e.g., "A" matches "A.")
        if (preg_match('/^[a-d]$/i', $normalizedModel) && preg_match('/^[a-d][.)]/i', $normalizedCorrect)) {
            return strtolower($normalizedModel[0]) === strtolower($normalizedCorrect[0]);
        }

        // Partial contains (model answer contains correct answer as substring)
        if (strlen($normalizedCorrect) > 5 && str_contains($normalizedModel, $normalizedCorrect)) {
            return true;
        }

        // Keyword matching for clinical answers
        $correctKeywords = array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/', '', $normalizedCorrect)));
        if (count($correctKeywords) >= 2) {
            $matchCount = 0;
            foreach ($correctKeywords as $keyword) {
                if (strlen($keyword) > 2 && str_contains($normalizedModel, $keyword)) {
                    $matchCount++;
                }
            }
            // If >70% of keywords match, consider it correct
            if ($matchCount / count($correctKeywords) >= 0.7) {
                return true;
            }
        }

        return false;
    }
}