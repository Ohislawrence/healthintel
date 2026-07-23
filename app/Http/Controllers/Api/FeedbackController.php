<?php

namespace App\Http\Controllers\Api;

use App\Models\UserFeedback;
use Illuminate\Http\Request;

class FeedbackController extends BaseController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bug,feature,improvement,other',
            'screen' => 'nullable|string|max:100',
            'message' => 'required|string|max:5000',
            'metadata' => 'nullable|array',
        ]);

        $feedback = UserFeedback::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return $this->success(['feedback' => $feedback], 'Feedback submitted. Thank you!', 201);
    }

    public function index(Request $request)
    {
        $query = UserFeedback::with('user')
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->latest();

        return $this->paginated($query->paginate(20));
    }

    public function update(Request $request, int $id)
    {
        $feedback = UserFeedback::findOrFail($id);
        $validated = $request->validate([
            'status' => 'sometimes|in:open,acknowledged,resolved,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);
        $feedback->update($validated);
        return $this->success(['feedback' => $feedback->fresh()]);
    }
}