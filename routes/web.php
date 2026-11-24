<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/projects/{project}/search', [ProjectController::class, 'search'])->name('projects.search');
Route::post('/projects/{project}/sources/link', [ProjectController::class, 'addLink'])->name('projects.link');
Route::post('/projects/{project}/sources/manual', [ProjectController::class, 'addManual'])->name('projects.manual');
Route::post('/projects/{project}/facts/{fact}/confirm', [ProjectController::class, 'confirmFact'])->name('projects.facts.confirm');
Route::post('/projects/{project}/facts', [ProjectController::class, 'addFact'])->name('projects.facts.add');
Route::post('/projects/{project}/resolve', [ProjectController::class, 'resolve'])->name('projects.resolve');

Route::post('/projects/{project}/generate', [SiteController::class, 'generate'])->name('projects.generate');
Route::post('/projects/{project}/publish', [SiteController::class, 'publish'])->name('projects.publish');
Route::get('/projects/{project}/preview', [SiteController::class, 'preview'])->name('projects.preview');
Route::get('/projects/{project}/sections/{section}/edit', [SiteController::class, 'editSection'])->name('projects.sections.edit');
Route::post('/projects/{project}/sections/{section}', [SiteController::class, 'updateSection'])->name('projects.sections.update');
Route::post('/projects/{project}/sections/{section}/regenerate', [SiteController::class, 'regenerate'])->name('projects.sections.regenerate');

Route::get('/s/{slug}', [SiteController::class, 'public'])->name('site.public');
