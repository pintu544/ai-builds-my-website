<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class OpenAiCompatibleDriver implements AiClient
{
    public function generateSections(array $facts, ?string $onlyKind = null): array
    {
        $key = config('services.ai.key');
        if (! $key) {
            throw new AiUnavailableException('AI is not configured (AI_API_KEY missing).');
        }
        $base = rtrim(config('services.ai.base_url'), '/');
        try {
            $response = Http::withToken($key)->timeout(60)->post($base . '/chat/completions', [
                'model' => config('services.ai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => SectionPrompt::system()],
                    ['role' => 'user', 'content' => SectionPrompt::user($facts, $onlyKind)],
                ],
                'temperature' => 0.4,
            ]);
        } catch (\Throwable $e) {
            throw new AiUnavailableException('AI request failed: ' . $e->getMessage());
        }
        if (! $response->successful()) {
            throw new AiUnavailableException('AI provider returned HTTP ' . $response->status() . '.');
        }
        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new AiUnavailableException('AI returned an empty response.');
        }
        return SectionPrompt::parse($text);
    }
}
