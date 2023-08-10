<?php

namespace Tests\Unit\Services\Prompt\Learning;

use PHPUnit\Framework\TestCase;
use App\Services\Ai\Prompt\Learning\HintsPrompt;

class HintsPromptTest extends TestCase
{
    public function test_get_messages(): void
    {
        $hp = new HintsPrompt([
            'question' => 'What is PHP?'
        ]);

        $messages = $hp->getMessages();
        $this->assertEquals([
            [
                'role' => 'system',
                'content' => $hp->getSystemPrompt()
            ],
            [
                'role' => 'user',
                'content' => $hp->getUserPrompt()
            ]
        ], $messages);
    }
}
