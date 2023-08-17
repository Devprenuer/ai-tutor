<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Grader\Grader;
use Carbon\Carbon;

use App\Models\Question;
use App\Models\Answer;

class AnswerController extends Controller
{
    public function answerQuestion(Request $request, string $questionId)
    {
        $request->validate([
            'answer' => 'required|string'
        ]);

        $question = Question::findOrFail($questionId);
        $user = $request->user();
        
        // if user has never seen question before, fail
        $view = $question->getMostRecentViewByUser($user->id);
        if (!$view) {
            return response()->json([
                'message' => 'You must view the question before answering it'
            ], 403);
        }

        try {
            $score = Grader::getScore(
                $question,
                $request->input('answer')
            );
    
            $answer = Answer::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer' => $request->input('answer'),
                'score' => $score,
                'time_taken' => (
                    Carbon::now()
                        ->diffInSeconds($view->created_at)
                )
            ]);

            // if the user has not answered the question before
            // increment the questions answered count
            $answeredPreviously = Answer::where('user_id', $user->id)
                ->where('question_id', $question->id)
                ->count() > 1;

            if (!$answeredPreviously) {
                $user->increment('questions_answered_count');
                $question->increment('answers_count');
            }

            return response()->json([
                // TODO: generate some strings for the message
                'message' => 'Question answered successfully',
                'answer' => $answer
            ]);

        } catch (\Exception $e) {
            // currently will only throw exception if question type
            // is not supported
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
