@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp
<section class="page-heading"><div><p class="eyebrow">RINGKASAN HARI INI</p><h1>{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}<span class="heading-dot">.</span></h1><p class="muted">Lihat progres proyek dan tugas yang perlu kamu prioritaskan.</p></div><div class="heading-actions"><a class="btn" href="{{ route('projects.index') }}"><x-icon name="plus" size="17"/>Proyek baru</a><a class="btn primary" href="{{ route('tasks.index') }}"><x-icon name="plus" size="17"/>Tugas baru</a></div></section>
<section class="overview" aria-label="Ringkasan workspace">
    <a class="stat-card" href="{{ route('projects.index') }}"><div class="stat-top"><span>Proyek</span><span class="stat-icon neutral"><x-icon name="book" size="18"/></span></div><strong>{{ str_pad($stats['projects'], 2, '0', STR_PAD_LEFT) }}</strong><small>Semua proyek milikmu</small></a>
    <a class="stat-card" href="{{ route('tasks.index', ['status' => 'in_progress']) }}"><div class="stat-top"><span>In progress</span><span class="stat-icon amber"><x-icon name="clock" size="18"/></span></div><strong>{{ str_pad($stats['in_progress'], 2, '0', STR_PAD_LEFT) }}</strong><small>Sedang dikerjakan</small></a>
    <a class="stat-card" href="{{ route('tasks.index', ['status' => 'done']) }}"><div class="stat-top"><span>Selesai</span><span class="stat-icon green"><x-icon name="check" size="18"/></span></div><strong>{{ str_pad($stats['done'], 2, '0', STR_PAD_LEFT) }}</strong><small>Tugas telah selesai</small></a>
    <a class="stat-card overdue-stat" href="{{ route('tasks.index', ['deadline' => 'overdue']) }}"><div class="stat-top"><span>Terlambat</span><span class="stat-icon rose"><x-icon name="flag" size="18"/></span></div><strong>{{ str_pad($stats['overdue'], 2, '0', STR_PAD_LEFT) }}</strong><small>{{ $stats['overdue'] ? 'Perlu perhatianmu' : 'Semua terkendali' }} →</small></a>
</section>
<section class="momentum"><span class="momentum-icon"><x-icon name="spark" size="23"/></span><div class="momentum-copy"><strong>{{ $stats['progress'] === 100 && $stats['tasks'] ? 'Semua tugas selesai' : 'Progres keseluruhan' }}</strong><p>{{ $stats['done'] }} dari {{ $stats['tasks'] }} tugas telah diselesaikan.</p></div><div class="progress-area"><div><span>Penyelesaian</span><strong>{{ $stats['progress'] }}%</strong></div><progress value="{{ $stats['progress'] }}" max="100">{{ $stats['progress'] }}%</progress></div></section>
<div class="dashboard-grid">
    <section class="panel"><div class="section-heading"><div class="section-title"><h2>Proyek terbaru</h2><span class="count-pill">{{ $projects->count() }}</span></div><a class="text-link" href="{{ route('projects.index') }}">Lihat semua →</a></div><div class="compact-projects">
        @forelse($projects as $project)
            @php
                $percent = $project->tasks_count ? (int) round($project->completed_tasks_count / $project->tasks_count * 100) : 0;
            @endphp
            <a class="compact-project" href="{{ route('projects.show', $project) }}"><span class="project-symbol">{{ mb_strtoupper(mb_substr($project->name, 0, 1)) }}</span><div><strong>{{ $project->name }}</strong><small>{{ $project->tasks_count }} tugas · {{ \App\Models\Project::STATUSES[$project->status] }}</small><progress value="{{ $percent }}" max="100">{{ $percent }}%</progress></div><b>{{ $percent }}%</b></a>
        @empty <div class="empty-mini">Belum ada proyek. Buat proyek pertamamu.</div> @endforelse
    </div></section>
    <section class="panel"><div class="section-heading"><div class="section-title"><h2>Prioritas berikutnya</h2></div><a class="text-link" href="{{ route('tasks.index') }}">Semua tugas →</a></div><div class="upcoming-list">
        @forelse($upcomingTasks as $task)
            <a href="{{ route('tasks.index', ['project_id' => $task->project_id]) }}" class="upcoming-item"><span class="status-dot {{ $task->status }}"></span><div><strong>{{ $task->title }}</strong><small>{{ $task->project?->name ?? 'Tanpa proyek' }}</small></div><time @class(['overdue' => $task->isOverdue()])>{{ $task->due_date?->translatedFormat('d M') ?? 'Tanpa tanggal' }}</time></a>
        @empty <div class="empty-mini">Tidak ada tugas aktif saat ini.</div> @endforelse
    </div></section>
</div>
@endsection
