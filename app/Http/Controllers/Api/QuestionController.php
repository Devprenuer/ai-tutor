<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use App\Services\Ai\Prompt\Learning\QuestionPrompt;
use App\Services\Ai\Response\JsonResponseHandler;
use App\Models\Topic;
use App\Models\Question;

class QuestionController extends Controller
{
    public function __construct(AiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    public function question(Request $request)
    {
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
        $question = $topic
            ->questions
            ->where('difficulty_level', $difficultyLevel)
            // TODO exclude previously asked questions
            ->last();

        if ($question) {
            return response()->json([
                'question' => $question
            ]);
        }


        $prompt = new QuestionPrompt([
            'topic' => $topic->topic,
            'difficulty_level' => $difficultyLevel,
            'industry' => $topic->industry->industry,
            // TODO fetch previous questions from database
            // send previous questions to prompt
            /*
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
                ]
            ],
            */
        ]);

        $questionData = $this->aiClient->chat(
            $prompt, new JsonResponseHandler()
        );

        $question = Question::create([
            'question' => $questionData->question,
            'difficulty_level' => $questionData->difficulty_level,
            'topic_id' => $topic->id
        ]);

        return response()->json([
            'question' => $question
        ]);
    }
}
