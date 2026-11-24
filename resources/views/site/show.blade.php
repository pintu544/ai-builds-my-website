<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $project->business_name }} — {{ $project->city }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900">
@if($preview)<div class="bg-amber-100 text-amber-900 text-center text-sm py-2">Preview — this site is not published yet.</div>@endif
@foreach($project->sections as $section)
  @php $c = $section->content; @endphp
  @switch($section->kind)
    @case('hero')
      <section class="bg-slate-900 text-white py-20 px-4 text-center">
        <h1 class="text-4xl font-bold mb-3">{{ $c['heading'] ?? $project->business_name }}</h1>
        @if(!empty($c['subheading']))<p class="text-lg text-slate-300 max-w-2xl mx-auto">{{ $c['subheading'] }}</p>@endif
      </section>
      @break
    @case('about')
      <section class="max-w-3xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-3">{{ $c['heading'] ?? 'About' }}</h2>
        <p class="text-slate-600 whitespace-pre-line">{{ $c['body'] ?? '' }}</p>
      </section>
      @break
    @case('services')
      <section class="bg-slate-50 py-12 px-4">
        <div class="max-w-3xl mx-auto">
          <h2 class="text-2xl font-bold mb-4">{{ $c['heading'] ?? 'Services' }}</h2>
          <ul class="grid sm:grid-cols-2 gap-3">
            @foreach($c['items'] ?? [] as $item)<li class="bg-white border border-slate-200 rounded px-4 py-3">{{ $item }}</li>@endforeach
          </ul>
        </div>
      </section>
      @break
    @case('prices')
      <section class="max-w-3xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-4">{{ $c['heading'] ?? 'Prices' }}</h2>
        <ul class="divide-y divide-slate-100">
          @foreach($c['items'] ?? [] as $item)<li class="py-2.5 flex justify-between"><span>{{ $item }}</span></li>@endforeach
        </ul>
      </section>
      @break
    @case('gallery')
      <section class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-4">{{ $c['heading'] ?? 'Gallery' }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          @foreach($project->sources->where('type', 'photo') as $photo)
            <img src="{{ asset('storage/' . $photo->path) }}" alt="{{ $project->business_name }}" class="rounded object-cover w-full h-48">
          @endforeach
        </div>
      </section>
      @break
    @case('contact')
      <section class="bg-slate-50 py-12 px-4">
        <div class="max-w-3xl mx-auto">
          <h2 class="text-2xl font-bold mb-3">{{ $c['heading'] ?? 'Contact' }}</h2>
          <p class="text-slate-600 whitespace-pre-line">{{ $c['body'] ?? '' }}</p>
        </div>
      </section>
      @break
    @case('footer')
      <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-500">{{ $c['text'] ?? $project->business_name }}</footer>
      @break
  @endswitch
@endforeach
</body>
</html>
