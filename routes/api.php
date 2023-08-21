<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\LessonController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::group(['prefix' => 'question'], function () {
        Route::get('/', [QuestionController::class, 'question'])
        ->name('api.question.getQuestion');
    
        Route::get('/{question_id}/hint', [QuestionController::class, 'questionHint'])
            ->name('api.question.getHint');
    
        Route::post('/{question_id}/answer', [AnswerController::class, 'answerQuestion'])
            ->name('api.question.answer');
    });

    Route::group(['prefix' => 'lesson'], function () {
        Route::get('/', [LessonController::class, 'index'])
            ->name('api.lesson.index');
        
        Route::get('/{lesson_id}', [LessonController::class, 'show'])
            ->name('api.lesson.show');
    });
});
