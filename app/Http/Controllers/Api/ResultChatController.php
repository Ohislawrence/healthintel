<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\ResultConversation;
use App\Models\ResultMessage;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class ResultChatController extends BaseController
{
    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    /**
     * Start a new conversation about a lab submission.
     */
    public function startConversation(Request $request)
    {
        $validated = $request->validate([
            'lab_submission_id' => 'required|exists:lab_submissions,id',
            'initial_message' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $submission = $user->labSubmissions()->findOrFail($validated['lab_submission_id']);

        // Create conversation
        $title = strlen($validated['initial_message']) > 80
            ? substr($validated['initial_message'], 0, 77) . '...'
            : $validated['initial_message'];

        $conversation = ResultConversation::create([
            'user_id' => $user->id,
            'lab_submission_id' => $submission->id,
            'title' => $title,
        ]);

        // Save user message
        ResultMessage::create([
            'result_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['initial_message'],
        ]);

        // Build context from the interpretation
        $interpretationText = $submission->interpretation?->interpretation_text
            ?? 'Lab results were submitted but no interpretation is available.';

        // Build initial context message for the AI
        $contextMessage = "The patient's lab submission (ID: {$submission->id}) interpretation:\n\n{$interpretationText}\n\nThe patient now asks:";

        // Get AI response
        $conversationHistory = [
            ['role' => 'assistant', 'content' => "I have your lab results. Here's what I can tell you: \n\n{$interpretationText}\n\nWhat would you like to know more about?"],
        ];

        $aiResponse = $this->deepSeek->chatAboutResult(
            $conversationHistory,
            $validated['initial_message']
        );

        if (!$aiResponse) {
            $aiResponse = "I'm sorry, I couldn't process your question right now. Please try again or consult your doctor about your lab results. This is NOT medical advice.";
        }

        // Save AI response
        ResultMessage::create([
            'result_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        return $this->success([
            'conversation' => $conversation->load('messages'),
        ], 'Conversation started', 201);
    }

    /**
     * Send a follow-up message in an existing conversation.
     */
    public function sendMessage(Request $request, int $conversationId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $conversation = ResultConversation::where('user_id', $user->id)
            ->with('messages')
            ->findOrFail($conversationId);

        // Save user message
        ResultMessage::create([
            'result_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Build conversation history from existing messages
        $history = $conversation->messages->map(fn($msg) => [
            'role' => $msg->role,
            'content' => $msg->content,
        ])->toArray();

        // Get AI response
        $aiResponse = $this->deepSeek->chatAboutResult($history, $validated['message']);

        if (!$aiResponse) {
            $aiResponse = "I'm sorry, I couldn't process your question right now. Please try again or consult your doctor. This is NOT medical advice.";
        }

        // Save AI response
        $assistantMessage = ResultMessage::create([
            'result_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        return $this->success([
            'message' => $assistantMessage,
        ], 'Message sent');
    }

    /**
     * List all conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $conversations = $request->user()
            ->resultConversations()
            ->withCount('messages')
            ->with(['labSubmission.testPanel:id,name'])
            ->latest()
            ->paginate(20);

        return $this->paginated($conversations);
    }

    /**
     * Get a single conversation with all messages.
     */
    public function show(Request $request, int $id)
    {
        $conversation = ResultConversation::where('user_id', $request->user()->id)
            ->with(['messages', 'labSubmission.testPanel:id,name'])
            ->findOrFail($id);

        return $this->success(['conversation' => $conversation]);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, int $id)
    {
        $conversation = ResultConversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $conversation->messages()->delete();
        $conversation->delete();

        return $this->success(null, 'Conversation deleted');
    }
}