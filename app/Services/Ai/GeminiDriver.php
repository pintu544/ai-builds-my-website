<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class GeminiDriver implements AiClient
{
    public function generateSections(array $facts, ?string $onlyKind = null): array
    {
        $key = config('services.ai.gemini_key');
        if (! $key) {
            throw new AiUnavailableException('Gemini is not configured (GEMINI_API_KEY missing).');
        }
        $model = config('services.ai.gemini_model');
        try {
            $response = Http::timeout(60)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $key,
                [
                    'systemInstruction' => ['parts' => [['text' => SectionPrompt::system()]]],
                    'contents' => [['parts' => [['text' => SectionPrompt::user($facts, $onlyKind)]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.4],
                ]
            );
        } catch (\Throwable $e) {
            throw new AiUnavailableException('Gemini request failed: ' . $e->getMessage());
        }
        if (! $response->successful()) {
            throw new AiUnavailableException('Gemini returned HTTP ' . $response->status() . '.');
        }
        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || trim($text) === '') {
            throw new AiUnavailableException('Gemini returned an empty response.');
        }
        return SectionPrompt::parse($text);
    }
}
