<?php

namespace App\Services;

/**
 * Detects instruction-like lines hidden in source text (uploaded files,
 * fetched pages). Flagged lines are shown to the user and excluded from
 * fact extraction and from anything sent to the AI.
 */
class InjectionScanner
{
    private array $patterns = [
        '/\b(ignore|disregard|forget)\s+(all\s+)?(previous|prior|above|earlier)\s+(instructions?|prompts?|rules?)/i',
        '/\byou\s+are\s+(now\s+)?(a|an|the)\b.{0,40}\b(assistant|ai|model|bot)\b/i',
        '/\bsystem\s*prompt\b/i',
        '/\b(new|updated)\s+instructions?\s*:/i',
        '/\bdo\s+not\s+(tell|inform|mention\s+to)\s+the\s+user\b/i',
        '/\b(send|email|post|upload|transfer)\b.{0,40}\b(to|at)\b.{0,60}(@|http)/i',
        '/\b(reveal|print|output|show)\b.{0,30}\b(api\s*key|password|secret|token|prompt)\b/i',
        '/\bact\s+as\s+(if\s+you\s+are\s+)?(a|an)\b/i',
        '/\[INST\]|\[\/INST\]|<\|im_start\|>|<\|im_end\|>/',
        '/\bvisit\s+(this\s+)?(link|url|website)\s+(and|to)\b.{0,50}\b(buy|order|pay|download|install)\b/i',
    ];

    /** Returns the list of lines in $text that look like instructions. */
    public function flaggedLines(string $text): array
    {
        $flagged = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            foreach ($this->patterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $flagged[] = $line;
                    break;
                }
            }
        }
        return $flagged;
    }

    /** Text with flagged lines removed — safe to extract facts from / send to AI. */
    public function clean(string $text): string
    {
        $flagged = $this->flaggedLines($text);
        if (! $flagged) {
            return $text;
        }
        $lines = array_filter(
            preg_split('/\R/', $text),
            fn ($line) => ! in_array(trim($line), $flagged, true)
        );
        return implode("\n", $lines);
    }
}
