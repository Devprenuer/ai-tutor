<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use App\Services\Ai\Prompt\Learning\QuestionPrompt;
use App\Services\Ai\Response\JsonResponseHandler;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserQuestionView;

class QuestionController extends Controller
{
    public function __construct(AiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    public function question(Request $request)
    {
        $this->user = $request->user();

        $request->validate([
            'topic' => 'required|string|exists:topics,id',
            'difficulty_level' => 'required|integer|between:1,10'
        ]);

        $topic = Topic::find(
            $request->query('topic')
        );

        $difficultyLevel = $request->query('difficulty_level');

        // fetch all questions from database under topic 
        // matching the difficulty level, excluding those previously asked
        // to the current user.      
        $question = Question::whereNotViewedByUser($this->user->id)
            ->where('topic_id', $topic->id)
            ->where('difficulty_level', $difficultyLevel)
            ->limit(1)
            ->skip($page || 0)
            ->get()
            ->last();

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
}
