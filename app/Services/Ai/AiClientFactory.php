<?php

namespace App\Services\Ai;

class AiClientFactory
{
    public static function make(): AiClient
    {
        return match (config('services.ai.provider')) {
            'gemini' => new GeminiDriver(),
            default => new OpenAiCompatibleDriver(),
        };
    }
}
