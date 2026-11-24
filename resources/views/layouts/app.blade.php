<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'AI Builds My Website' }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <a href="{{ route('projects.index') }}" class="font-bold text-lg">AI Builds My Website</a>
    <span class="text-sm text-slate-500">Facts first. Nothing invented.</span>
  </div>
</header>
<main class="max-w-6xl mx-auto px-4 py-6">
  @if(session('ok'))<div class="mb-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('ok') }}</div>@endif
  @if(session('warn'))<div class="mb-4 rounded bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">{{ session('warn') }}</div>@endif
  @if($errors->any())<div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif
  @yield('content')
</main>
</body>
</html>
