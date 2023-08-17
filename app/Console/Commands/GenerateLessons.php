<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

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

        // input the user email
        $userEmail = $this->ask('Enter your email');
        $user = User::where('email', $userEmail)->first();

        var_dump($user);
        return;
    }
}
