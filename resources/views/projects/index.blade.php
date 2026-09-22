@extends('layouts.app')
@section('title', 'Proyek')
@section('page-title', 'Proyek')

@section('content')
<section class="page-heading"><div><p class="eyebrow">MANAJEMEN PROYEK</p><h1>Proyek<span class="heading-dot">.</span></h1><p class="muted">Kelola tujuan besar dan pantau progresnya dalam satu tempat.</p></div><button type="button" class="btn primary" data-new-project><x-icon name="plus" size="18"/>Proyek baru</button></section>

@if($errors->any())<div class="error-box" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<form action="{{ route('projects.index') }}" method="GET" class="filter-bar project-filters">
    <label class="search-box"><x-icon name="search" size="18"/><input id="search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari proyek…" maxlength="120" aria-label="Cari proyek"></label>
    <select name="status" aria-label="Filter status proyek" data-submit-change><option value="">Semua status</option>@foreach(\App\Models\Project::STATUSES as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select>
    <button class="btn" type="submit">Cari</button>
    @if(!empty($filters['q']) || !empty($filters['status']))<a class="btn ghost" href="{{ route('projects.index') }}">Reset</a>@endif
</form>

<section class="project-grid" aria-label="Daftar proyek">
    @forelse($projects as $project)
        @php
            $percent = $project->tasks_count ? (int) round($project->completed_tasks_count / $project->tasks_count * 100) : 0;
        @endphp
        <article class="project-card">
            <div class="project-card-head"><span class="project-symbol large">{{ mb_strtoupper(mb_substr($project->name, 0, 1)) }}</span><span class="status-badge status-{{ $project->status }}">{{ \App\Models\Project::STATUSES[$project->status] }}</span><div class="project-menu"><button class="icon-btn" type="button" data-edit-project="{{ $project->id }}" aria-label="Edit {{ $project->name }}"><x-icon name="edit" size="17"/></button><button class="icon-btn danger" type="button" data-delete-project="{{ $project->id }}" aria-label="Hapus {{ $project->name }}"><x-icon name="delete" size="17"/></button></div></div>
            <a href="{{ route('projects.show', $project) }}" class="project-card-body"><h2>{{ $project->name }}</h2><p>{{ $project->description ?: 'Belum ada deskripsi untuk proyek ini.' }}</p></a>
            <div class="project-meta"><span><x-icon name="calendar" size="14"/>{{ $project->deadline?->translatedFormat('d M Y') ?? 'Tanpa deadline' }}</span><span>{{ $project->completed_tasks_count }}/{{ $project->tasks_count }} tugas</span></div>
            <div class="project-progress"><div><span>Progres</span><strong>{{ $percent }}%</strong></div><progress value="{{ $percent }}" max="100">{{ $percent }}%</progress></div>
            <a href="{{ route('projects.show', $project) }}" class="project-open">Buka tugas <span>→</span></a>
        </article>
    @empty
        <div class="empty-state full-span"><span class="empty-icon"><x-icon name="book" size="32"/></span><h3>Mulai dengan satu proyek.</h3><p>Kelompokkan tugas agar progresmu lebih mudah dipantau.</p><button type="button" class="btn primary" data-new-project><x-icon name="plus" size="17"/>Buat proyek</button></div>
    @endforelse
</section>

<div class="list-footer">
    <span>
        @if($projects->total())
            Menampilkan {{ $projects->firstItem() }}–{{ $projects->lastItem() }} dari {{ $projects->total() }} proyek
        @endif
    </span>
    <div class="pagination">
        @if($projects->previousPageUrl())
            <a href="{{ $projects->previousPageUrl() }}" class="btn small-btn">← Sebelumnya</a>
        @endif
        @if($projects->hasMorePages())
            <a href="{{ $projects->nextPageUrl() }}" class="btn small-btn">Berikutnya →</a>
        @endif
    </div>
</div>

@php
$projectData = $projects->mapWithKeys(fn ($project) => [$project->id => [
    'id' => $project->id, 'name' => $project->name, 'description' => $project->description,
    'deadline' => $project->deadline?->format('Y-m-d'), 'status' => $project->status,
    'url' => route('projects.update', $project),
]]);
@endphp
@endsection

@push('dialogs')
<dialog id="project-dialog" aria-labelledby="project-dialog-title"><form method="POST" action="{{ route('projects.store') }}" id="project-form" data-store-url="{{ route('projects.store') }}">@csrf<input type="hidden" name="_method" id="project-method" value="POST"><input type="hidden" name="_project_id" id="project-id">
    <header class="dialog-header"><div><span class="eyebrow">DETAIL PROYEK</span><h2 id="project-dialog-title">Buat proyek</h2></div><button type="button" class="icon-btn" data-close-dialog aria-label="Tutup"><x-icon name="x"/></button></header>
    <div class="dialog-fields"><label>Nama proyek <span class="required">*</span><input name="name" id="project-name" maxlength="120" required placeholder="Contoh: Website Open Recruitment"></label><label>Deskripsi <span class="optional">opsional</span><textarea name="description" id="project-description" rows="4" maxlength="3000" placeholder="Tujuan dan ruang lingkup proyek…"></textarea></label><div class="field-row"><label>Deadline <span class="optional">opsional</span><input name="deadline" id="project-deadline" type="date"></label><label>Status <select name="status" id="project-status" required>@foreach(\App\Models\Project::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label></div></div>
    <footer class="dialog-footer"><span>Kolom bertanda * wajib diisi.</span><button type="button" class="btn" data-close-dialog>Batal</button><button type="submit" class="btn primary" id="save-project">Buat proyek</button></footer>
</form></dialog>
<dialog id="delete-project-dialog" class="delete-dialog"><form method="POST" id="delete-project-form">@csrf @method('DELETE')<div class="dialog-fields"><span class="delete-symbol"><x-icon name="delete" size="26"/></span><h2>Hapus proyek?</h2><p class="muted"><span id="delete-project-title"></span> beserta seluruh tugas dan lampirannya akan dihapus permanen.</p></div><footer class="dialog-footer"><button class="btn" type="button" data-close-dialog>Batal</button><button class="btn danger-btn" type="submit">Hapus proyek</button></footer></form></dialog>
<script id="project-data" type="application/json">{!! json_encode($projectData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script id="project-old-input" type="application/json">{!! json_encode(session()->hasOldInput() ? old() : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush
