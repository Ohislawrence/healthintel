<?php

namespace App\Http\Controllers\Api;

use App\Models\LabSubmission;
use App\Models\SymptomCheckFunnel;
use App\Models\Symptom;
use Illuminate\Http\Request;

class ActivityController extends BaseController
{
    /**
     * Return a unified, filterable recent-activity feed for the current user.
     *
     * Supports type=lab|symptom (default: both) and limit (default 5, max 25).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $type = $request->query('type');
        $limit = min((int) $request->query('limit', 5), 25);

        $activities = collect();

        // ── Lab submissions ──
        if ($type === null || $type === 'lab') {
            $submissions = LabSubmission::where('user_id', $user->id)
                ->with(['testPanel:id,name', 'interpretation:id,lab_submission_id,status'])
                ->latest('submitted_at')
                ->limit($limit)
                ->get();

            foreach ($submissions as $s) {
                $activities->push([
                    'type' => 'lab',
                    'id' => $s->id,
                    'title' => $s->submission_type === 'pdf' ? 'PDF Upload' : ($s->testPanel->name ?? 'Lab Result'),
                    'subtitle' => $s->credits_used . ' credit' . ($s->credits_used > 1 ? 's' : ''),
                    'status' => $s->interpretation->status ?? 'pending',
                    'created_at' => ($s->submitted_at ?? $s->created_at)->toISOString(),
                    'route' => '/lab-results/submission/' . $s->id,
                ]);
            }
        }

        // ── Symptom checks ──
        if ($type === null || $type === 'symptom') {
            $funnels = SymptomCheckFunnel::where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            // Resolve symptom slugs → names in one query.
            $allSlugs = $funnels->flatMap(fn ($f) => $f->symptoms_selected ?? [])->unique()->values()->all();
            $symptomNames = Symptom::whereIn('slug', $allSlugs)->pluck('name', 'slug');

            foreach ($funnels as $f) {
                $slugs = $f->symptoms_selected ?? [];
                $names = array_values(array_filter(array_map(fn ($slug) => $symptomNames[$slug] ?? null, $slugs)));
                $count = count($names);

                $title = $count > 0
                    ? ($count === 1 ? $names[0] : implode(', ', array_slice($names, 0, 2)) . ($count > 2 ? ' +' . ($count - 2) : ''))
                    : 'Symptom check';

                $activities->push([
                    'type' => 'symptom',
                    'id' => $f->id,
                    'title' => $title,
                    'subtitle' => ($count ?? 0) . ' symptom' . ($count !== 1 ? 's' : ''),
                    'status' => $f->stage ?? 'checked',
                    'created_at' => $f->created_at->toISOString(),
                    'route' => '/symptom-checker',
                ]);
            }
        }

        // Sort merged feed newest-first, then take the requested limit.
        $activities = $activities
            ->sortByDesc(fn ($a) => $a['created_at'])
            ->take($limit)
            ->values();

        return $this->success([
            'activities' => $activities,
            'total' => $activities->count(),
            'counts' => [
                'lab' => LabSubmission::where('user_id', $user->id)->count(),
                'symptom' => SymptomCheckFunnel::where('user_id', $user->id)->count(),
                'all' => LabSubmission::where('user_id', $user->id)->count()
                    + SymptomCheckFunnel::where('user_id', $user->id)->count(),
            ],
        ]);
    }
}