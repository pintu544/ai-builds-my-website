<?php

namespace App\Services\Ai;

/**
 * Tries each configured provider in order and uses the first that answers.
 * A provider that is unconfigured or down raises AiUnavailableException,
 * which moves the chain on; if every provider fails, the exception from the
 * last one propagates and SiteGenerator uses its facts-only fallback.
 */
class CompositeAiClient implements AiClient
{
    /** @param AiClient[] $clients */
    public function __construct(private array $clients)
    {
    }

    public function generateSections(array $facts, ?string $onlyKind = null): array
    {
        $last = null;
        foreach ($this->clients as $client) {
            try {
                return $client->generateSections($facts, $onlyKind);
            } catch (AiUnavailableException $e) {
                $last = $e;
            }
        }
        throw $last ?? new AiUnavailableException('No AI provider is configured.');
    }
}
