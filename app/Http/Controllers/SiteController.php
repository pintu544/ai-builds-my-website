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

    public function generate(Project $project)
    {
        $result = $this->generator->generate($project);
        return back()->with($result['path'] === 'ai' ? 'ok' : 'warn', $result['note']);
    }

    public function regenerate(Project $project, SiteSection $section)
    {
        abort_unless($section->project_id === $project->id, 404);
        $result = $this->generator->regenerateSection($project, $section);
        return back()->with($result['path'] === 'ai' ? 'ok' : 'warn', $result['note']);
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
        foreach ((array) $request->input('content', []) as $key => $value) {
            if (is_string($value)) {
                $content[$key] = $value;
            }
        }
        if ($request->filled('items')) {
            $content['items'] = array_values(array_filter(array_map('trim', preg_split('/\R/', $request->input('items')))));
        }
        $section->update(['content' => $content]);
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
