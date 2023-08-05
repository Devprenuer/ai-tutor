<?php namespace App\Services\Ai;

use OpenAI;
use App\Services\Ai\Prompt\PromptInterface;

class AiClient
{
    private $client;

    public function __construct(OpenAI $openai)
    {
        $this->client = $openai;
    }

    public function chat(PromptInterface $prompt, callable $callback): mixed
    {
        // Combine the prompts into an array of message arrays
        $messages = $prompt->getMessages();

        if (empty($messages)) {
            throw new \Exception("No messages were provided to the AI client. Please check your prompt {get_class($prompt)}.");
        }

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages
        ]);

        return $callback($response);
    }
}
