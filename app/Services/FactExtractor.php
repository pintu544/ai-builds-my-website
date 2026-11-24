<?php

namespace App\Services;

use App\Models\Source;

/**
 * Deterministic fact extraction. No AI: regex/rule based, every fact keeps
 * the source id and the exact snippet it came from, so the user can audit
 * every value on the facts board.
 */
class FactExtractor
{
    public function __construct(private InjectionScanner $scanner)
    {
    }

    /**
     * Extract facts from a source's raw text.
     * Returns rows: [field, value, snippet, flagged_injection]
     */
    public function extract(Source $source): array
    {
        $text = $source->raw_text ?? '';
        if (trim($text) === '') {
            return [];
        }

        $facts = [];

        // Instruction-like lines become flagged facts (visible, never used).
        foreach ($this->scanner->flaggedLines($text) as $line) {
            $facts[] = ['field' => 'note', 'value' => $line, 'snippet' => $line, 'flagged_injection' => true];
        }

        $clean = $this->scanner->clean($text);

        // Phone numbers (Indian mobile/landline and international).
        if (preg_match_all('/(?<!\d)(?:\+?91[\s-]?)?[6-9]\d{4}[\s-]?\d{5}(?!\d)|(?<!\d)0\d{2,4}[\s-]?\d{6,8}(?!\d)/', $clean, $m)) {
            foreach (array_unique($m[0]) as $phone) {
                $facts[] = $this->row('phone', preg_replace('/\s+/', ' ', trim($phone)), $clean, $phone);
            }
        }

        // Emails.
        if (preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $clean, $m)) {
            foreach (array_unique($m[0]) as $email) {
                $facts[] = $this->row('email', strtolower($email), $clean, $email);
            }
        }

        // Opening hours lines.
        foreach (preg_split('/\R/', $clean) as $line) {
            $line = trim($line);
            if (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun|daily|everyday|open)\b/i', $line)
                && preg_match('/\d{1,2}(:\d{2})?\s*(am|pm)\b/i', $line)) {
                $facts[] = ['field' => 'hours', 'value' => $line, 'snippet' => $line, 'flagged_injection' => false];
            }
        }

        // Price rows: "Item .... ₹250" or "Item - Rs. 250" or CSV "Item,250".
        foreach (preg_split('/\R/', $clean) as $line) {
            $line = trim($line);
            if (preg_match('/^(.{2,60}?)[\s,.\-–—:]*\s*(?:₹|rs\.?|inr)\s*([\d,]+(?:\.\d{1,2})?)\s*$/i', $line, $mm)
                || preg_match('/^(.{2,60}?),\s*([\d,]+(?:\.\d{1,2})?)\s*$/', $line, $mm)) {
                $item = trim($mm[1], " \t,-–—:");
                if ($item !== '' && ! preg_match('/total|subtotal|tax|gst/i', $item)) {
                    $facts[] = ['field' => 'price', 'value' => $item . ' — ₹' . $mm[2], 'snippet' => $line, 'flagged_injection' => false];
                }
            }
        }

        // Service-like lines from a price list also count as services.
        if ($source->type === 'pricelist') {
            foreach ($facts as $f) {
                if ($f['field'] === 'price') {
                    $name = trim(strstr($f['value'], ' — ', true) ?: '');
                    if ($name !== '') {
                        $facts[] = ['field' => 'service', 'value' => $name, 'snippet' => $f['snippet'], 'flagged_injection' => false];
                    }
                }
            }
        }

        return $facts;
    }

    private function row(string $field, string $value, string $text, string $needle): array
    {
        $pos = mb_stripos($text, $needle);
        $snippet = $pos === false ? $needle : trim(mb_substr($text, max(0, $pos - 60), 160));
        return ['field' => $field, 'value' => $value, 'snippet' => $snippet, 'flagged_injection' => false];
    }
}
