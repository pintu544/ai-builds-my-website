@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Projects</h1>
  <a href="{{ route('projects.create') }}" class="bg-slate-900 text-white px-4 py-2 rounded text-sm">+ New project</a>
</div>
@if($projects->isEmpty())
  <p class="text-slate-500">No projects yet. Create one: give a business name and city, add its links and files, and the app builds a website only from facts it can show you.</p>
@else
  <div class="grid gap-4">
  @foreach($projects as $p)
    <a href="{{ route('projects.show', $p) }}" class="block bg-white border border-slate-200 rounded p-4 hover:border-slate-400">
      <div class="flex justify-between">
        <div><span class="font-semibold">{{ $p->business_name }}</span> <span class="text-slate-500">· {{ $p->city }}</span></div>
        <span class="text-xs uppercase tracking-wide px-2 py-1 rounded bg-slate-100">{{ $p->status }}</span>
      </div>
      <div class="text-sm text-slate-500 mt-1">{{ $p->sources_count ?? $p->sources->count() }} sources · {{ $p->facts->count() }} facts
      @if($p->published_at) · live at <span class="text-emerald-700">/s/{{ $p->slug }}</span>@endif</div>
    </a>
  @endforeach
  </div>
@endif
@endsection
