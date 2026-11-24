<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Turns every input (links, files, search) into a Source row with raw text
 * and a status. Blocked or failed sources are first-class states: the user
 * sees exactly what happened and can paste the content manually instead.
 */
class IngestionService
{
    public function __construct(private FactExtractor $extractor)
    {
    }

    public function addLink(Project $project, string $url, string $type): Source
    {
        $source = $project->sources()->create(['type' => $type, 'url' => $url, 'status' => 'pending']);
        $this->fetchLink($source);
        $this->extractFacts($source);
        return $source;
    }

    public function fetchLink(Source $source): void
    {
        if ($this->isPrivateUrl($source->url)) {
            $source->update(['status' => 'failed', 'note' => 'That URL points at a private or local address, so it was not fetched. Paste the content manually instead.']);
            return;
        }
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(25)->get($source->url);
        } catch (\Throwable $e) {
            $source->update(['status' => 'failed', 'note' => 'Could not reach this link: ' . $e->getMessage()]);
            return;
        }

        $body = $response->body() ?? '';
        $text = $this->htmlToText($body);

        if (in_array($response->status(), [401, 403, 429], true) || $this->looksLikeLoginWall($source->url, $text)) {
            $source->update([
                'status' => 'blocked',
                'raw_text' => null,
                'note' => 'This site blocked automatic reading (HTTP ' . $response->status() . ' / login wall). Paste the page content below instead — nothing was guessed.',
            ]);
            return;
        }

        if (! $response->successful() || mb_strlen(trim($text)) < 40) {
            $source->update([
                'status' => 'failed',
                'note' => 'The link returned HTTP ' . $response->status() . ' with too little readable content. You can paste the content manually.',
            ]);
            return;
        }

        $source->update(['status' => 'ok', 'raw_text' => mb_substr($text, 0, 60000)]);
    }

    private function looksLikeLoginWall(string $url, string $text): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $social = str_contains($host, 'instagram.') || str_contains($host, 'facebook.') || str_contains($host, 'linkedin.');
        if ($social && mb_strlen($text) < 1200) {
            return true;
        }
        $needles = ['log in to continue', 'sign in to continue', 'you must log in', 'please log in to see', 'verify you are human', 'are you a robot', 'before you continue to google', 'enable javascript', 'you need to enable javascript', 'this page requires javascript', 'consent.google'];
        $head = mb_strtolower(mb_substr($text, 0, 1500));
        foreach ($needles as $n) {
            if (str_contains($head, $n)) {
                return true;
            }
        }
        return false;
    }

    /** Block SSRF: never fetch loopback / private / reserved addresses. */
    private function isPrivateUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return true;
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) {
            return true; // unresolvable hosts cannot be fetched safely
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }
        return false;
    }

    public function htmlToText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html);
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/h[1-6])>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    public function addPdf(Project $project, UploadedFile $file): Source
    {
        $path = $file->store('uploads/' . $project->id, 'local');
        $source = $project->sources()->create(['type' => 'pdf', 'path' => $path, 'status' => 'pending', 'url' => $file->getClientOriginalName()]);
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile(Storage::disk('local')->path($path));
            $text = trim($pdf->getText());
            if ($text === '') {
                $source->update(['status' => 'failed', 'note' => 'No readable text in this PDF (it may be scanned images). Paste the content manually.']);
            } else {
                $source->update(['status' => 'ok', 'raw_text' => mb_substr($text, 0, 60000)]);
            }
        } catch (\Throwable $e) {
            $source->update(['status' => 'failed', 'note' => 'Could not parse this PDF: ' . $e->getMessage()]);
        }
        $this->extractFacts($source);
        return $source;
    }

    public function addPriceList(Project $project, UploadedFile $file): Source
    {
        $path = $file->store('uploads/' . $project->id, 'local');
        $text = (string) file_get_contents(Storage::disk('local')->path($path));
        $source = $project->sources()->create([
            'type' => 'pricelist', 'path' => $path, 'status' => 'ok',
            'url' => $file->getClientOriginalName(), 'raw_text' => mb_substr($text, 0, 60000),
        ]);
        $this->extractFacts($source);
        return $source;
    }

    public function addPhoto(Project $project, UploadedFile $file): Source
    {
        $path = $file->store('uploads/' . $project->id, 'public');
        return $project->sources()->create([
            'type' => 'photo', 'path' => $path, 'status' => 'ok',
            'url' => $file->getClientOriginalName(),
            'note' => 'Photo stored for the site gallery.',
        ]);
    }

    public function addManualText(Project $project, string $text, ?int $replacesSourceId = null): Source
    {
        $source = $project->sources()->create([
            'type' => 'manual', 'status' => 'ok', 'raw_text' => mb_substr($text, 0, 60000),
            'note' => $replacesSourceId ? 'Pasted by the user to replace blocked source #' . $replacesSourceId . '.' : 'Pasted by the user.',
        ]);
        $this->extractFacts($source);
        return $source;
    }

    /**
     * Web search for the business (Exa). Returns candidate result rows and
     * stores them as search sources the user can pick from — this is also
     * how same-name businesses get disambiguated.
     */
    public function searchWeb(Project $project): array
    {
        $key = config('services.exa.key');
        if (! $key) {
            return ['ok' => false, 'note' => 'Web search is not configured (EXA_API_KEY missing) — search skipped, nothing was faked.', 'results' => []];
        }
        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(30)
                ->post('https://api.exa.ai/search', [
                    'query' => $project->business_name . ' ' . $project->city,
                    'numResults' => 6,
                    'contents' => ['text' => ['maxCharacters' => 4000]],
                ]);
            if (! $response->successful()) {
                return ['ok' => false, 'note' => 'Web search failed (HTTP ' . $response->status() . '). You can add links manually.', 'results' => []];
            }
            $results = [];
            foreach ($response->json('results', []) as $r) {
                $source = $project->sources()->create([
                    'type' => 'search', 'url' => $r['url'] ?? null, 'status' => 'ok',
                    'raw_text' => mb_substr(($r['title'] ?? '') . "\n" . ($r['text'] ?? ''), 0, 20000),
                    'note' => 'Web search result — confirm this is the right business before using its facts.',
                ]);
                $this->extractFacts($source);
                $results[] = $source;
            }
            return ['ok' => true, 'note' => null, 'results' => $results];
        } catch (\Throwable $e) {
            return ['ok' => false, 'note' => 'Web search failed: ' . $e->getMessage(), 'results' => []];
        }
    }

    public function extractFacts(Source $source): void
    {
        $source->facts()->delete();
        foreach ($this->extractor->extract($source) as $row) {
            $source->facts()->create($row + ['project_id' => $source->project_id]);
        }
    }
}
