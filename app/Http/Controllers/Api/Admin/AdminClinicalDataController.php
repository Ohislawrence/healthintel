<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\InterpretationPanel;
use App\Models\ReferenceRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminClinicalDataController extends BaseController
{
    // ══════════════════════════════════════════════════
    // REFERENCE RANGES
    // ══════════════════════════════════════════════════

    public function ranges(Request $request)
    {
        $q = ReferenceRange::query();

        if ($request->search) {
            $q->where(function ($sq) use ($request) {
                $sq->where('test_name', 'like', "%{$request->search}%")
                   ->orWhere('test_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $q->where('category', $request->category);
        }

        $ranges = $q->orderBy('category')->orderBy('test_name')->paginate(50);

        return $this->paginated($ranges);
    }

    public function rangeStore(Request $request)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|max:100',
            'test_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'sex' => 'required|in:male,female,all',
            'age_min_years' => 'nullable|numeric',
            'age_max_years' => 'nullable|numeric',
            'pregnancy_applicable' => 'boolean',
            'pregnancy_trimester' => 'nullable|integer|min:1|max:3',
            'range_low' => 'required|numeric',
            'range_high' => 'required|numeric',
            'critical_low' => 'nullable|numeric',
            'critical_high' => 'nullable|numeric',
            'unit' => 'required|string|max:50',
            'source' => 'nullable|string|max:255',
        ]);

        $range = ReferenceRange::create(array_merge($validated, [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]));

        return $this->success(['range' => $range], 'Reference range created', 201);
    }

    public function rangeUpdate(Request $request, $id)
    {
        $range = ReferenceRange::findOrFail($id);

        $validated = $request->validate([
            'test_code' => 'sometimes|string|max:100',
            'test_name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
            'sex' => 'sometimes|in:male,female,all',
            'age_min_years' => 'nullable|numeric',
            'age_max_years' => 'nullable|numeric',
            'pregnancy_applicable' => 'boolean',
            'pregnancy_trimester' => 'nullable|integer|min:1|max:3',
            'range_low' => 'sometimes|numeric',
            'range_high' => 'sometimes|numeric',
            'critical_low' => 'nullable|numeric',
            'critical_high' => 'nullable|numeric',
            'unit' => 'sometimes|string|max:50',
            'source' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $range->update(array_merge($validated, [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]));

        return $this->success(['range' => $range->fresh()], 'Reference range updated');
    }

    public function rangeDestroy($id)
    {
        ReferenceRange::findOrFail($id)->delete();
        return $this->success(null, 'Reference range deleted');
    }

    public function rangeCategories()
    {
        $categories = ReferenceRange::select('category')->distinct()->orderBy('category')->pluck('category');
        return $this->success(['categories' => $categories]);
    }

    // ══════════════════════════════════════════════════
    // INTERPRETATION PANELS
    // ══════════════════════════════════════════════════

    public function panels(Request $request)
    {
        $panels = InterpretationPanel::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(25);

        return $this->paginated($panels);
    }

    public function panelStore(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:interpretation_panels',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_codes' => 'required|array|min:1',
            'test_codes.*' => 'string',
            'layout_sections' => 'nullable|array',
        ]);

        $panel = InterpretationPanel::create(array_merge($validated, [
            'status' => 'draft',
            'version' => 1,
        ]));

        return $this->success(['panel' => $panel], 'Panel created', 201);
    }

    public function panelUpdate(Request $request, $id)
    {
        $panel = InterpretationPanel::findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'test_codes' => 'sometimes|array|min:1',
            'test_codes.*' => 'string',
            'layout_sections' => 'nullable|array',
            'status' => 'sometimes|in:draft,approved,deprecated',
        ]);

        if ($request->has('status') && $request->status === 'approved') {
            $validated['approved_by'] = $request->user()->id;
            $validated['approved_at'] = now();
        }

        $panel->update(array_merge($validated, [
            'version' => $panel->version + 1,
        ]));

        return $this->success(['panel' => $panel->fresh()], 'Panel updated');
    }

    public function panelDestroy($id)
    {
        InterpretationPanel::findOrFail($id)->delete();
        return $this->success(null, 'Panel deleted');
    }

    // ══════════════════════════════════════════════════
    // MEDICATION EFFECTS
    // ══════════════════════════════════════════════════

    public function medicationEffects(Request $request)
    {
        $effects = DB::table('medication_effects')
            ->when($request->search, fn($q) =>
                $q->where('medication_name', 'like', "%{$request->search}%")
                  ->orWhere('test_code', 'like', "%{$request->search}%")
            )
            ->orderBy('medication_name')
            ->paginate(50);

        return $this->paginated($effects);
    }

    public function medicationEffectStore(Request $request)
    {
        $validated = $request->validate([
            'medication_slug' => 'required|string|max:100',
            'medication_name' => 'required|string|max:255',
            'test_code' => 'required|string|max:100',
            'expected_effect' => 'required|in:elevates,lowers,no_effect,variable',
            'severity' => 'required|in:mild,moderate,significant',
            'clinician_note' => 'nullable|string',
        ]);

        DB::table('medication_effects')->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return $this->success(null, 'Medication effect created', 201);
    }

    public function medicationEffectUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'medication_slug' => 'sometimes|string|max:100',
            'medication_name' => 'sometimes|string|max:255',
            'test_code' => 'sometimes|string|max:100',
            'expected_effect' => 'sometimes|in:elevates,lowers,no_effect,variable',
            'severity' => 'sometimes|in:mild,moderate,significant',
            'clinician_note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::table('medication_effects')->where('id', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        return $this->success(null, 'Medication effect updated');
    }

    public function medicationEffectDestroy($id)
    {
        DB::table('medication_effects')->where('id', $id)->delete();
        return $this->success(null, 'Medication effect deleted');
    }
}