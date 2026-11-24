<?php

namespace App\Services\Ai;

interface AiClient
{
    /**
     * Generate website sections from confirmed facts only.
     * Returns a list of ['kind' => string, 'content' => array, 'fact_keys' => string[]].
     *
     * @throws AiUnavailableException when the provider is down or unconfigured.
     */
    public function generateSections(array $facts, ?string $onlyKind = null): array;
}
