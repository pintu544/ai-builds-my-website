@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold">{{ $project->business_name }} <span class="text-slate-400 font-normal">· {{ $project->city }}</span></h1>
    <p class="text-sm text-slate-500">Status: <strong>{{ $project->status }}</strong>
    @if($project->published_at) · Published: <a class="text-emerald-700 underline" href="{{ route('site.public', $project->slug) }}" target="_blank">/s/{{ $project->slug }}</a>@endif</p>
  </div>
  <div class="flex gap-2">
    <form method="POST" action="{{ route('projects.search', $project) }}">@csrf
      <button class="border border-slate-300 bg-white px-3 py-2 rounded text-sm">Search the web</button>
    </form>
    <form method="POST" action="{{ route('projects.generate', $project) }}">@csrf
      <button class="bg-slate-900 text-white px-3 py-2 rounded text-sm">Generate website</button>
    </form>
    @if($project->sections->isNotEmpty())
    <form method="POST" action="{{ route('projects.publish', $project) }}">@csrf
      <button class="bg-emerald-700 text-white px-3 py-2 rounded text-sm">Publish</button>
    </form>
    @endif
  </div>
</div>

@if(count($unresolved))
<div class="mb-6 rounded border border-amber-300 bg-amber-50 p-4">
  <h2 class="font-semibold text-amber-900 mb-2">Sources disagree — your call needed</h2>
  <p class="text-sm text-amber-800 mb-3">Publishing is blocked until each conflict is resolved. The app will not guess.</p>
  @foreach($unresolved as $field => $facts)
    <div class="mb-3">
      <div class="text-sm font-medium capitalize">{{ $field }}</div>
      <div class="flex flex-wrap gap-2 mt-1">
      @foreach($facts as $fact)
        <form method="POST" action="{{ route('projects.resolve', $project) }}">@csrf
          <input type="hidden" name="field" value="{{ $field }}">
          <input type="hidden" name="fact_id" value="{{ $fact->id }}">
          <button class="text-sm border border-amber-400 bg-white rounded px-3 py-1.5" title="From: {{ $fact->source?->label() }}">
            Use “{{ $fact->value }}” <span class="text-slate-400">({{ $fact->source?->label() }})</span>
          </button>
        </form>
      @endforeach
      </div>
    </div>
  @endforeach
</div>
@endif

<div class="grid lg:grid-cols-2 gap-6">
  <section class="bg-white border border-slate-200 rounded p-5">
    <h2 class="font-semibold mb-3">Sources</h2>
    @forelse($project->sources as $source)
      <div class="border-b border-slate-100 py-3 last:border-0">
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium">{{ $source->label() }}</span>
          <span class="text-xs px-2 py-0.5 rounded {{ $source->status === 'ok' ? 'bg-emerald-100 text-emerald-800' : ($source->status === 'blocked' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600') }}">{{ $source->status }}</span>
          <span class="text-slate-400 truncate">{{ $source->url }}</span>
          <form method="POST" action="{{ route('projects.sources.delete', [$project, $source]) }}" class="ml-auto" onsubmit="return confirm('Remove this source and its facts?')">@csrf @method('DELETE')
            <button class="text-xs text-red-500 hover:underline">Remove</button>
          </form>
        </div>
        @if($source->note)<p class="text-sm text-slate-500 mt-1">{{ $source->note }}</p>@endif
        @if($source->raw_text)
          <details class="mt-1"><summary class="text-xs text-slate-400 cursor-pointer">What was read ({{ $source->facts->count() }} facts extracted)</summary>
          <pre class="text-xs bg-slate-50 border border-slate-100 rounded p-2 mt-1 whitespace-pre-wrap max-h-48 overflow-auto">{{ \Illuminate\Support\Str::limit($source->raw_text, 3000) }}</pre></details>
        @endif
        @php $replaced = $project->sources->where('type', 'manual')->contains(fn ($m) => str_contains($m->note ?? '', 'replace blocked source #' . $source->id)); @endphp
        @if($replaced)
          <p class="text-xs text-emerald-700 mt-1">Replaced by pasted content below.</p>
        @elseif(in_array($source->status, ['blocked', 'failed']))
          <form method="POST" action="{{ route('projects.manual', $project) }}" class="mt-2">@csrf
            <input type="hidden" name="replaces" value="{{ $source->id }}">
            <textarea name="text" required rows="3" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" placeholder="Paste the page / profile content here instead…"></textarea>
            <button class="text-sm border border-slate-300 rounded px-3 py-1 mt-1">Add pasted content</button>
          </form>
        @endif
      </div>
    @empty
      <p class="text-sm text-slate-500">No sources yet.</p>
    @endforelse

    <form method="POST" action="{{ route('projects.link', $project) }}" class="mt-4 flex gap-2">@csrf
      <input name="url" type="url" required class="flex-1 border border-slate-300 rounded px-2 py-1.5 text-sm" placeholder="Add another link…">
      <select name="type" class="border border-slate-300 rounded px-2 py-1.5 text-sm">
        <option value="link">Website</option><option value="google">Google</option><option value="social">Social</option>
      </select>
      <button class="text-sm bg-slate-900 text-white rounded px-3">Read it</button>
    </form>
    <form method="POST" action="{{ route('projects.manual', $project) }}" class="mt-3">@csrf
      <textarea name="text" rows="2" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" placeholder="Or paste any text about the business (brochure text, WhatsApp info, notes)…"></textarea>
      <button class="text-sm border border-slate-300 rounded px-3 py-1 mt-1">Add pasted content</button>
    </form>
  </section>

  <section class="bg-white border border-slate-200 rounded p-5">
    <h2 class="font-semibold mb-3">Facts board</h2>
    <p class="text-sm text-slate-500 mb-3">Every value the website may use, with the source it came from. Facts from your own links, files and pastes are used as they are — unless sources disagree, in which case you pick the winner above. Facts from web search are used only after you confirm them, because a search result might be a different business with the same name.</p>
    @php $flagged = $project->facts->where('flagged_injection', true); $normal = $project->facts->where('flagged_injection', false)->groupBy('field'); @endphp
    @foreach($normal as $field => $facts)
      <div class="mb-4">
        <div class="text-xs uppercase tracking-wide text-slate-400 capitalize">{{ $field }} @if(isset($conflicts[$field])) <span class="text-amber-600 normal-case">— conflict</span>@endif</div>
        @foreach($facts as $fact)
          <div class="flex items-start justify-between gap-2 py-1.5 border-b border-slate-50">
            <div class="text-sm">
              <span class="{{ $fact->confirmed ? 'font-medium' : '' }}">{{ $fact->value }}</span>
              <span class="block text-xs text-slate-400">from {{ $fact->source?->label() ?? 'the user' }} — “{{ \Illuminate\Support\Str::limit($fact->snippet, 90) }}”</span>
            </div>
            @if($fact->confirmed)<span class="text-xs text-emerald-700">confirmed</span>
            @else
            <form method="POST" action="{{ route('projects.facts.confirm', [$project, $fact]) }}">@csrf
              <button class="text-xs border border-slate-300 rounded px-2 py-1">Confirm</button>
            </form>@endif
          </div>
        @endforeach
      </div>
    @endforeach

    @if($flagged->isNotEmpty())
      <div class="mt-4 rounded border border-red-200 bg-red-50 p-3">
        <div class="text-sm font-semibold text-red-800">Hidden instructions found — excluded</div>
        <p class="text-xs text-red-700 mb-2">These lines look like instructions planted in a source. They are shown to you and are never used as facts or sent to the AI.</p>
        @foreach($flagged as $fact)<div class="text-xs text-red-800 py-0.5">“{{ $fact->value }}” <span class="text-red-400">— from {{ $fact->source?->label() }}</span></div>@endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('projects.facts.add', $project) }}" class="mt-5 flex gap-2">@csrf
      <select name="field" class="border border-slate-300 rounded px-2 py-1.5 text-sm">
        <option value="phone">Phone</option><option value="email">Email</option><option value="address">Address</option>
        <option value="hours">Hours</option><option value="description">Description</option><option value="service">Service</option>
      </select>
      <input name="value" required class="flex-1 border border-slate-300 rounded px-2 py-1.5 text-sm" placeholder="Add a fact yourself…">
      <button class="text-sm border border-slate-300 rounded px-3">Add</button>
    </form>
  </section>
</div>

@if($project->sections->isNotEmpty())
<section class="bg-white border border-slate-200 rounded p-5 mt-6">
  <h2 class="font-semibold mb-1">Website sections</h2>
  <p class="text-sm text-slate-500 mb-3">Generated only from confirmed facts. <a class="underline" href="{{ route('projects.preview', $project) }}">Preview the site</a></p>
  @foreach($project->sections as $section)
    <div class="border-b border-slate-100 py-3 last:border-0 flex items-start justify-between gap-3">
      <div>
        <div class="text-sm font-medium capitalize">{{ $section->kind }}</div>
        <div class="text-sm text-slate-600">{{ $section->content['heading'] ?? $section->content['title'] ?? '' }}</div>
        <div class="text-xs text-slate-400">@if(!empty($section->content['_edited'])) Edited by you @else Built from facts: {{ implode(', ', $section->fact_keys ?? []) ?: 'none' }} @endif</div>
      </div>
      <div class="flex gap-2 shrink-0">
        <a href="{{ route('projects.sections.edit', [$project, $section]) }}" class="text-xs border border-slate-300 rounded px-2 py-1">Edit</a>
        <form method="POST" action="{{ route('projects.sections.regenerate', [$project, $section]) }}">@csrf
          <button class="text-xs border border-slate-300 rounded px-2 py-1">Regenerate</button>
        </form>
      </div>
    </div>
  @endforeach
</section>
@endif
@endsection
