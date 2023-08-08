<?php namespace App\Services\Ai\Response;

interface ResponseHandlerInterface
{
    public static function extractPayload($response): mixed;
}
