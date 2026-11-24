@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-2">New project</h1>
<p class="text-slate-500 mb-6 text-sm">Give what you have — any mix. The app reads it all, shows you the facts it found, and asks you about anything unclear. It never invents details.</p>
<form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded p-6 grid gap-4 max-w-2xl">
  @csrf
  <label class="grid gap-1 text-sm">Business name *
    <input name="business_name" required class="border border-slate-300 rounded px-3 py-2" placeholder="e.g. Sharma Bakery">
  </label>
  <label class="grid gap-1 text-sm">City *
    <input name="city" required class="border border-slate-300 rounded px-3 py-2" placeholder="e.g. Mumbai">
  </label>
  <label class="grid gap-1 text-sm">Website link
    <input name="website_url" type="url" class="border border-slate-300 rounded px-3 py-2" placeholder="https://…">
  </label>
  <label class="grid gap-1 text-sm">Google Business listing link
    <input name="google_url" type="url" class="border border-slate-300 rounded px-3 py-2" placeholder="https://maps.google.com/…">
  </label>
  <label class="grid gap-1 text-sm">Social media links (one per line)
    <textarea name="social_urls" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="https://instagram.com/…"></textarea>
  </label>
  <label class="grid gap-1 text-sm">Brochure / menu PDF
    <input name="pdf" type="file" accept="application/pdf" class="text-sm">
  </label>
  <label class="grid gap-1 text-sm">Price list (CSV or TXT)
    <input name="pricelist" type="file" accept=".csv,.txt" class="text-sm">
  </label>
  <label class="grid gap-1 text-sm">Photos (for the gallery)
    <input name="photos[]" type="file" accept="image/*" multiple class="text-sm">
  </label>
  <button class="bg-slate-900 text-white px-4 py-2 rounded text-sm justify-self-start">Create project &amp; read sources</button>
</form>
@endsection
