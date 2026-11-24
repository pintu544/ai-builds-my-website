<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SiteSection;
use App\Services\SiteGenerator;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(private SiteGenerator $generator)
    {
    }

    /** Editing a live site unpublishes it first, so the public page never
     *  silently changes or serves facts that are back in dispute. */
    private function unpublishIfPublished(Project $project): bool
    {
        if ($project->published_at) {
            $project->update(['published_at' => null, 'status' => 'draft']);
            return true;
        }
        return false;
    }

    public function generate(Project $project)
    {
        $wasLive = $this->unpublishIfPublished($project);
        $result = $this->generator->generate($project);
        $note = $result['note'] . ($wasLive ? ' The site was live and has been unpublished while you work — publish again when ready.' : '');
        return back()->with($result['path'] === 'ai' ? 'ok' : 'warn', $note);
    }

    public function regenerate(Project $project, SiteSection $section)
    {
        abort_unless($section->project_id === $project->id, 404);
        $wasLive = $this->unpublishIfPublished($project);
        $result = $this->generator->regenerateSection($project, $section);
        $note = $result['note'] . ($wasLive ? ' The site was live and has been unpublished while you work — publish again when ready.' : '');
        return back()->with($result['path'] === 'ai' ? 'ok' : 'warn', $note);
    }

    public function editSection(Project $project, SiteSection $section)
    {
        abort_unless($section->project_id === $project->id, 404);
        return view('projects.edit-section', ['project' => $project, 'section' => $section]);
    }

    public function updateSection(Request $request, Project $project, SiteSection $section)
    {
        abort_unless($section->project_id === $project->id, 404);
        $content = $section->content;
        // A field that was submitted counts even when cleared (empty -> null by middleware).
        foreach (array_keys((array) $request->input('content', [])) as $key) {
            $value = $request->input('content.' . $key);
            $content[$key] = is_string($value) ? $value : '';
        }
        if ($request->has('items')) {
            $content['items'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $request->input('items')))));
        }
        $this->unpublishIfPublished($project);
        $content['_edited'] = true; // the board stops claiming it was built purely from facts
        $section->update(['content' => $content, 'fact_keys' => []]);
        return redirect()->route('projects.show', $project)->with('ok', 'Section updated.');
    }

    public function publish(Project $project)
    {
        $unresolved = $project->unresolvedConflicts();
        if ($unresolved) {
            return back()->with('warn', 'Publishing is blocked: sources disagree on ' . implode(', ', array_keys($unresolved)) . '. Pick the correct value on the facts board first — the app will not guess.');
        }
        if ($project->sections()->count() === 0) {
            return back()->with('warn', 'Generate the website before publishing.');
        }
        $project->update(['status' => 'published', 'published_at' => now()]);
        return back()->with('ok', 'Published! Public URL: /s/' . $project->slug);
    }

    public function preview(Project $project)
    {
        return view('site.show', ['project' => $project, 'preview' => true]);
    }

    public function public(string $slug)
    {
        $project = Project::where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        return view('site.show', ['project' => $project, 'preview' => false]);
    }
}
