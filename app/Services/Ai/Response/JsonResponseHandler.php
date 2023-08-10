<?php namespace App\Services\Ai\Response;

use App\Services\Ai\Response\ResponseHandlerInterface;

class JsonResponseHandler implements ResponseHandlerInterface
{
    public static function extractPayload($response): mixed
    {
        return json_decode($response->choices[0]->message->content);
    }
}