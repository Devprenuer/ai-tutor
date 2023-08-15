<?php namespace App\Services\Grader;

use App\Models\Question;

class Grader
{
    public static function getScore(Question $question, string $answer)
    {
        switch ($question->question_type) {
            case Question::QUESTION_TYPES['MULTIPLE_CHOICE']['ID']:
                // normalize answer input
                $answer = strtolower(trim($answer));
                $correctAnswer = strtolower(trim($question->multiple_choice_answer));
                // TODO: currently 10 or 0 if answer is correct or not
                // but in the future we should judge by how close the answer is
                // to the correct answer
                return $question->multiple_choice_answer === $answer ? 10 : 0;
            case Question::QUESTION_TYPES['CODING']['ID']:
                // TODO: implement
                throw new \Exception('Not implemented');
        }
    }
}