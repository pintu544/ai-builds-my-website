<?php

namespace App\Services\Ai;

class AiClientFactory
{
    public static function make(): AiClient
    {
        // The configured provider goes first; the other configured provider
        // is the backup, so one dead key never takes generation down.
        $gemini = new GeminiDriver();
        $openAi = new OpenAiCompatibleDriver();

        return new CompositeAiClient(
            config('services.ai.provider') === 'gemini'
                ? [$gemini, $openAi]
                : [$openAi, $gemini]
        );
    }
}
