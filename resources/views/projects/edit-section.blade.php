@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-4">Edit section: <span class="capitalize">{{ $section->kind }}</span> — {{ $project->business_name }}</h1>
<form method="POST" action="{{ route('projects.sections.update', [$project, $section]) }}" class="bg-white border border-slate-200 rounded p-6 grid gap-4 max-w-2xl">
  @csrf
  @foreach($section->content as $key => $value)
    @if(is_string($value))
      <label class="grid gap-1 text-sm capitalize">{{ $key }}
        <textarea name="content[{{ $key }}]" rows="{{ $key === 'body' ? 6 : 2 }}" class="border border-slate-300 rounded px-3 py-2">{{ $value }}</textarea>
      </label>
    @endif
  @endforeach
  @if(isset($section->content['items']) && is_array($section->content['items']))
    <label class="grid gap-1 text-sm">Items (one per line)
      <textarea name="items" rows="6" class="border border-slate-300 rounded px-3 py-2">{{ implode("\n", $section->content['items']) }}</textarea>
    </label>
  @endif
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded text-sm">Save</button>
    <a href="{{ route('projects.show', $project) }}" class="border border-slate-300 px-4 py-2 rounded text-sm">Back</a>
  </div>
</form>
@endsection
