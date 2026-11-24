<?php

namespace App\Http\Controllers;

use App\Models\Fact;
use App\Models\Project;
use App\Services\IngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function __construct(private IngestionService $ingestion)
    {
    }

    public function index()
    {
        return view('projects.index', ['projects' => Project::latest()->get()]);
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:200',
            'city' => 'required|string|max:120',
            'website_url' => 'nullable|url',
            'google_url' => 'nullable|url',
            'social_urls' => 'nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
            'pricelist' => 'nullable|file|mimes:csv,txt|max:5120',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:10240',
        ]);

        $project = Project::create([
            'business_name' => $data['business_name'],
            'city' => $data['city'],
            'slug' => $this->uniqueSlug($data['business_name'], $data['city']),
            'status' => 'draft',
        ]);

        // The name and city the user typed are facts too — source: the user.
        $project->facts()->create(['field' => 'name', 'value' => $data['business_name'], 'snippet' => 'Typed by the user', 'confirmed' => true]);
        $project->facts()->create(['field' => 'city', 'value' => $data['city'], 'snippet' => 'Typed by the user', 'confirmed' => true]);

        if (! empty($data['website_url'])) {
            $this->ingestion->addLink($project, $data['website_url'], 'link');
        }
        if (! empty($data['google_url'])) {
            $this->ingestion->addLink($project, $data['google_url'], 'google');
        }
        foreach (array_filter(array_map('trim', preg_split('/\R/', $data['social_urls'] ?? ''))) as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $this->ingestion->addLink($project, $url, 'social');
            }
        }
        if ($request->hasFile('pdf')) {
            $this->ingestion->addPdf($project, $request->file('pdf'));
        }
        if ($request->hasFile('pricelist')) {
            $this->ingestion->addPriceList($project, $request->file('pricelist'));
        }
        foreach ($request->file('photos', []) as $photo) {
            $this->ingestion->addPhoto($project, $photo);
        }

        $searchNote = '';
        if (config('services.exa.key')) {
            $result = $this->ingestion->searchWeb($project);
            $searchNote = $result['ok']
                ? ' Web search results were added as candidate sources — their facts join the site only after you confirm them.'
                : ' ' . $result['note'];
        }

        return redirect()->route('projects.show', $project)->with('ok', 'Project created. Review the facts board — nothing is published until you confirm the facts.' . $searchNote);
    }

    public function show(Project $project)
    {
        $project->load(['sources.facts', 'facts.source', 'sections']);
        return view('projects.show', [
            'project' => $project,
            'conflicts' => $project->conflicts(),
            'unresolved' => $project->unresolvedConflicts(),
        ]);
    }

    public function search(Project $project)
    {
        $result = $this->ingestion->searchWeb($project);
        $msg = $result['ok']
            ? 'Web search done. Check each result is the right business before trusting its facts.'
            : $result['note'];
        return back()->with($result['ok'] ? 'ok' : 'warn', $msg);
    }

    public function addLink(Request $request, Project $project)
    {
        $data = $request->validate(['url' => 'required|url', 'type' => 'required|in:link,google,social']);
        $this->ingestion->addLink($project, $data['url'], $data['type']);
        return back()->with('ok', 'Link read. New facts are on the board.');
    }

    public function addManual(Request $request, Project $project)
    {
        $data = $request->validate([
            'text' => 'required|string|max:60000',
            'replaces' => 'nullable|integer',
        ]);
        $this->ingestion->addManualText($project, $data['text'], $data['replaces'] ?? null);
        return back()->with('ok', 'Pasted content added as a source and its facts extracted.');
    }

    public function confirmFact(Project $project, Fact $fact)
    {
        abort_unless($fact->project_id === $project->id, 404);
        $fact->update(['confirmed' => true]);
        return back()->with('ok', 'Fact confirmed.');
    }

    public function resolve(Request $request, Project $project)
    {
        $data = $request->validate(['field' => 'required|string', 'fact_id' => 'required|integer']);
        $fact = $project->facts()
            ->where('field', $data['field'])
            ->where('flagged_injection', false)
            ->findOrFail($data['fact_id']);
        $resolution = $project->resolution ?? [];
        $resolution[$data['field']] = ['fact' => $fact->id, 'seen' => $project->normalisedValues($data['field'])];
        $hadSections = $project->sections()->exists();
        $project->update(['resolution' => $resolution]);
        if ($hadSections) {
            // Sections may contain the losing value — never serve stale facts.
            $project->sections()->delete();
            $project->update(['status' => 'draft', 'published_at' => null]);
        }
        return back()->with('ok', 'Conflict resolved: ' . $data['field'] . ' = ' . $fact->value
            . ($hadSections ? ' The generated site used the old value, so it was cleared — generate again to rebuild from the resolved facts.' : ''));
    }

    public function deleteSource(Project $project, \App\Models\Source $source)
    {
        abort_unless($source->project_id === $project->id, 404);
        $source->facts()->delete();
        $source->delete();
        return back()->with('ok', 'Source removed, along with the facts that came only from it.');
    }

    public function addFact(Request $request, Project $project)
    {
        $data = $request->validate([
            'field' => 'required|in:phone,email,address,hours,description,service',
            'value' => 'required|string|max:2000',
        ]);
        $source = $project->sources()->create(['type' => 'manual', 'status' => 'ok', 'note' => 'Fact typed by the user.']);
        $project->facts()->create([
            'source_id' => $source->id, 'field' => $data['field'], 'value' => $data['value'],
            'snippet' => 'Typed by the user', 'confirmed' => true,
        ]);
        return back()->with('ok', 'Fact added and confirmed.');
    }

    private function uniqueSlug(string $name, string $city): string
    {
        $base = Str::limit(Str::slug($name . '-' . $city) ?: 'business', 180, '');
        $slug = $base;
        $i = 2;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
