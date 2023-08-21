<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Services\Ai\Prompt\Learning\LessonPrompt;
use App\Services\Ai\AiClient;
use App\Services\Ai\Response\JsonResponseHandler;

class GenerateLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-lessons-for-questions {question_ids}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate lessons for questions with the given ids (comma separated) with open ai gpt-4';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get the ids
        $questionIds = array_filter(
            explode(',', $this->argument('question_ids'))
        );

        if (empty($questionIds)) {
            $this->error('No question ids provided');
            return;
        }

        $questions = Question::whereIn('id', $questionIds)
            ->with('lessons', 'topic')
            ->get();

        if (count($questions) !== count($questionIds)) {
            $this->error('Some questions were not found');
            return;
        }

        $client = app(AiClient::class);
        $responseHandler = new JsonResponseHandler();

        foreach ($questions as $question) {
            if ($question->lessons->count()) {
                $this->info("Skipping question {$question->id} as it already has lessons");
                continue;
            }

            $this->info("Generating lessons for question {$question->id}...");
            // start timer
            $start = microtime(true);
            $prompt = new LessonPrompt(
                [
                    'question' => $question->question,
                    'topic' => $question->topic->topic,
                ]
            );
            $this->info("Question: {$question->question}");
            $this->info("Topic: {$question->topic->topic}");

            $lesson = $client->chat(
                $prompt,
                $responseHandler
            );

            $this->info("Lesson loaded: '{$lesson->title}' adding to database...");
            $question->lessons()->create([
                'title' => $lesson->title,
                'body' => $lesson->body,
                'excerpt' => $lesson->excerpt,
                'difficulty_level' => $question->difficulty_level,
                'topic_id' => $question->topic_id
            ]);
            $milliseconds = round((microtime(true) - $start) * 1000);
            $this->info("Lesson added to database in {$milliseconds}ms");
        }
    }
}
