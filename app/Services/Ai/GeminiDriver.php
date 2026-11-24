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
            throw new AiUnavailableException('Gemini request failed (network error).');
        }
        if (! $response->successful()) {
            throw new AiUnavailableException('Gemini returned HTTP ' . $response->status() . '.');
        }
        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || trim($text) === '') {
            throw new AiUnavailableException('Gemini returned an empty response.');
        }
        try {
            return SectionPrompt::parse($text);
        } catch (AiUnavailableException $e) {
            $retry = Http::timeout(60)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $key,
                [
                    'systemInstruction' => ['parts' => [['text' => SectionPrompt::system()]]],
                    'contents' => [['parts' => [['text' => SectionPrompt::user($facts, $onlyKind) . "\nYour previous reply was not valid JSON. Reply with ONLY the JSON object, no other text."]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.2],
                ]
            );
            $text2 = $retry->successful() ? $retry->json('candidates.0.content.parts.0.text') : null;
            if (! is_string($text2) || trim($text2) === '') {
                throw $e;
            }
            return SectionPrompt::parse($text2);
        }
    }
}
