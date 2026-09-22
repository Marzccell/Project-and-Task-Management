@extends('layouts.app')
@section('title', 'Tugas')
@section('page-title', 'Tugas')

@section('content')
<section class="page-heading"><div><p class="eyebrow">MANAJEMEN TUGAS</p><h1>Semua tugas<span class="heading-dot">.</span></h1><p class="muted">Cari, filter, dan pindahkan tugas tanpa kehilangan konteks proyek.</p></div><div class="heading-actions"><a class="btn" href="{{ route('tasks.export', request()->query()) }}"><x-icon name="arrow" size="17"/>Ekspor Excel</a><button type="button" class="btn primary" data-new-task @disabled($projects->isEmpty())><x-icon name="plus" size="18"/>Tugas baru <kbd>N</kbd></button></div></section>

@if($projects->isEmpty())<div class="notice-box">Buat proyek terlebih dahulu sebelum menambahkan tugas. <a href="{{ route('projects.index') }}">Buat proyek →</a></div>@endif
@if($errors->any())<div class="error-box" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<section class="overview compact-overview" aria-label="Statistik tugas">
    <a class="stat-card" href="{{ route('tasks.index') }}"><div class="stat-top"><span>Total</span><span class="stat-icon neutral"><x-icon name="list" size="18"/></span></div><strong>{{ str_pad($stats['total'], 2, '0', STR_PAD_LEFT) }}</strong><small>Semua tugas</small></a>
    <a class="stat-card" href="{{ route('tasks.index', ['status' => 'todo']) }}"><div class="stat-top"><span>To-do</span><span class="stat-icon neutral"><x-icon name="circle" size="18"/></span></div><strong>{{ str_pad($stats['todo'], 2, '0', STR_PAD_LEFT) }}</strong><small>Siap dikerjakan</small></a>
    <a class="stat-card" href="{{ route('tasks.index', ['status' => 'in_progress']) }}"><div class="stat-top"><span>In progress</span><span class="stat-icon amber"><x-icon name="clock" size="18"/></span></div><strong>{{ str_pad($stats['progress'], 2, '0', STR_PAD_LEFT) }}</strong><small>Sedang berjalan</small></a>
    <a class="stat-card" href="{{ route('tasks.index', ['status' => 'done']) }}"><div class="stat-top"><span>Done</span><span class="stat-icon green"><x-icon name="check" size="18"/></span></div><strong>{{ str_pad($stats['done'], 2, '0', STR_PAD_LEFT) }}</strong><small>Sudah selesai</small></a>
</section>

<section class="task-section" aria-label="Daftar tugas">
    <div class="section-heading"><div class="section-title"><h2>Tugas</h2><span class="count-pill">{{ $tasks->total() }}</span></div><div class="view-switch" aria-label="Tampilan">@foreach(['list' => 'Daftar', 'board' => 'Kanban'] as $key => $label)<a href="{{ route('tasks.index', array_merge(request()->except('page'), ['view' => $key])) }}" @class(['view-btn', 'selected' => $view === $key])><x-icon :name="$key" size="16"/>{{ $label }}</a>@endforeach</div></div>
    <form action="{{ route('tasks.index') }}" method="GET" class="filter-bar advanced-filters" id="filters"><input type="hidden" name="view" value="{{ $view }}">
        <label class="search-box"><x-icon name="search" size="18"/><input id="search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul atau deskripsi…" maxlength="160"><kbd>/</kbd></label>
        <select name="project_id" aria-label="Filter proyek" data-submit-change><option value="">Semua proyek</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string)($filters['project_id'] ?? '') === (string)$project->id)>{{ $project->name }}</option>@endforeach</select>
        <select name="status" aria-label="Filter status" data-submit-change><option value="">Semua status</option>@foreach(\App\Models\Task::STATUSES as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select>
        <select name="deadline" aria-label="Filter deadline" data-submit-change><option value="">Semua deadline</option><option value="overdue" @selected(($filters['deadline'] ?? '') === 'overdue')>Terlambat</option><option value="today" @selected(($filters['deadline'] ?? '') === 'today')>Jatuh tempo hari ini</option><option value="week" @selected(($filters['deadline'] ?? '') === 'week')>7 hari ke depan</option><option value="no_deadline" @selected(($filters['deadline'] ?? '') === 'no_deadline')>Tanpa deadline</option></select>
        <label class="date-filter"><span>Dari</span><input type="date" name="deadline_from" value="{{ $filters['deadline_from'] ?? '' }}"></label><label class="date-filter"><span>Sampai</span><input type="date" name="deadline_to" value="{{ $filters['deadline_to'] ?? '' }}"></label>
        <button class="btn search-btn" type="submit">Terapkan</button><a class="btn ghost" href="{{ route('tasks.index', ['view' => $view]) }}">Reset</a>
    </form>

    @if($tasks->isEmpty())
        <div class="empty-state"><span class="empty-icon"><x-icon name="check" size="32"/></span><h3>Tidak ada tugas ditemukan.</h3><p>Ubah filter atau buat tugas baru di salah satu proyek.</p>@if($projects->isNotEmpty())<button type="button" class="btn primary" data-new-task><x-icon name="plus" size="17"/>Buat tugas</button>@endif</div>
    @elseif($view === 'board')
        <p class="drag-hint">Tarik kartu ke kolom lain untuk memperbarui status.</p>
        <div class="kanban">@foreach(\App\Models\Task::STATUSES as $key => $label)<section class="kanban-column" data-drop-status="{{ $key }}"><div class="kanban-heading"><span class="status-dot {{ $key }}"></span><h3>{{ $label }}</h3><span data-column-count>{{ $tasks->where('status', $key)->count() }}</span></div><div class="kanban-dropzone">@forelse($tasks->where('status', $key) as $task)@include('tasks.card')@empty<p class="column-empty">Tarik tugas ke sini.</p>@endforelse</div></section>@endforeach</div>
    @else
        <div class="list-labels task-list-labels"><span>DETAIL TUGAS</span><span>PROYEK</span><span>STATUS</span><span></span></div><div class="task-list">@foreach($tasks as $task)@include('tasks.card')@endforeach</div>
    @endif

    <div class="list-footer">
        <span>
            @if($tasks->total())
                Menampilkan {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} dari {{ $tasks->total() }} tugas
            @endif
        </span>
        <div class="pagination">
            @if($tasks->previousPageUrl())
                <a href="{{ $tasks->previousPageUrl() }}" class="btn small-btn">← Sebelumnya</a>
            @endif
            @if($tasks->hasMorePages())
                <a href="{{ $tasks->nextPageUrl() }}" class="btn small-btn">Berikutnya →</a>
            @endif
        </div>
    </div>
</section>
@endsection

@push('dialogs')
@include('tasks.dialogs')
@endpush
