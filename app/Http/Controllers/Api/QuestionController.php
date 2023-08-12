<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use App\Services\Ai\Prompt\Learning\QuestionPrompt;
use App\Services\Ai\Prompt\Learning\HintsPrompt;
use App\Services\Ai\Response\JsonResponseHandler;
use Illuminate\Http\JsonResponse;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Hint;
use App\Models\UserQuestionView;

class QuestionController extends Controller
{
    public function __construct(AiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    public function question(Request $request): JsonResponse
    {
        $this->user = $request->user();

        $request->validate([
            'topic_id' => 'required|string|exists:topics,id',
            'difficulty_level' => 'required|integer|between:1,10',
            'page' => 'nullable|integer|min:1'
        ]);

        $page = $request->query('page', 1);

        $topic = Topic::find(
            $request->query('topic_id')
        );

        $difficultyLevel = $request->query('difficulty_level');

        // fetch all questions from database under topic 
        // matching the difficulty level, excluding those previously asked
        // to the current user.      
        $question = Question::whereNotViewedByUser($this->user->id)
            ->where('topic_id', $topic->id)
            ->where('difficulty_level', $difficultyLevel)
            ->skip($page - 1)
            ->take(1)
            ->orderBy('created_at', 'desc')
            ->get()
            ->first();

        if ($question) {
            $question->addViewByUser($this->user->id);
            return response()->json([
                'question' => $question
            ]);
        }

        $prompt = new QuestionPrompt([
            'topic' => $topic->topic,
            'difficulty_level' => $difficultyLevel,
            'industry' => $topic->industry->industry,
            'previous_questions' => Question::whereViewedByUser($this->user->id)
                ->where('topic_id', $topic->id)
                ->where('difficulty_level', $difficultyLevel)
                // TODO think of a better way to do this
                // all these questions are being sent to the AI
                // which is not ideal
                ->limit(50)
                ->get()
                ->toArray()
        ]);

        $questionData = $this->aiClient->chat(
            $prompt, new JsonResponseHandler()
        );

        $question = Question::create([
            'question' => $questionData->question,
            'difficulty_level' => $questionData->difficulty_level,
            'topic_id' => $topic->id
        ]);

        $question->addViewByUser($this->user->id);

        return response()->json([
            'question' => $question
        ]);
    }


    public function questionHint(Request $request, $question_id): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1'
        ]);

        $page = $request->query('page', 1);

        $question = Question::findOrFail(
            $question_id
        );

        $hint = Hint::where('question_id', $question->id)
            ->skip($page - 1)
            ->take(1)
            ->orderBy('created_at', 'asc')
            ->orderBy('helpfulness_level', 'asc')
            ->get()
            ->first();
        
        $user = $request->user();

        if ($hint) {
            if (!$hint->viewedByUser($user->id)) {
                $hint->addViewByUser($user->id);
            }

            return response()->json([
                'hint' => $hint
            ]);
        }

        $prompt = new HintsPrompt([
            'question' => $question->question
        ]);

        $hintData = $this->aiClient->chat(
            $prompt, new JsonResponseHandler()
        );

        for ($i = 0; $i < count($hintData); $i++) {
            $hintData[$i] = Hint::create([
                'hint' => $hintData[$i]->hint,
                'helpfulness_level' => $hintData[$i]->helpfulness_level,
                'question_id' => $question->id
            ]);
        }

        return response()->json([
            'hint' => $hintData[$page - 1]
        ]);
    }
}
