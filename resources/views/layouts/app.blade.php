@extends('layouts.base')

@section('body')
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><x-icon name="check" size="23"/></span>CampusFlow<span class="brand-dot">.</span></a>
        <div class="workspace-label"><span class="workspace-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div>{{ auth()->user()->name }}<small>Workspace pribadi</small></div></div>
        <span class="nav-caption">MENU</span>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])><x-icon name="grid" size="19"/><span>Dashboard</span></a>
            <a href="{{ route('projects.index') }}" @class(['nav-link', 'active' => request()->routeIs('projects.*')])><x-icon name="book" size="19"/><span>Proyek</span></a>
            <a href="{{ route('tasks.index') }}" @class(['nav-link', 'active' => request()->routeIs('tasks.*')])><x-icon name="list" size="19"/><span>Semua tugas</span></a>
            <a href="{{ route('tasks.index', ['deadline' => 'overdue']) }}" class="nav-link"><x-icon name="flag" size="19"/><span>Terlambat</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="focus-note shortcut-note"><strong>Akses cepat</strong><p><kbd>N</kbd> Buat tugas baru</p><p><kbd>/</kbd> Fokus pencarian</p></div>
            <div class="profile"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div><button class="icon-btn" type="button" data-theme-toggle aria-label="Ganti tema"><x-icon name="sun" size="18"/></button><form action="{{ route('logout') }}" method="POST">@csrf<button class="icon-btn" aria-label="Keluar"><x-icon name="logout" size="18"/></button></form></div>
        </div>
    </aside>
    <div class="main-shell">
        <header class="topbar"><div class="breadcrumb"><button class="icon-btn mobile-menu" type="button" id="menu-toggle" aria-label="Buka navigasi" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu"/></button><span>Workspace</span><span class="slash">/</span><strong>@yield('page-title', 'Dashboard')</strong></div><div class="topbar-right"><span class="today-label"><x-icon name="calendar" size="15"/>{{ today()->translatedFormat('D, d M Y') }}</span><span class="avatar small">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span></div></header>
        <main class="content">
            @if(session('success'))<div class="toast" role="status"><x-icon name="check" size="18"/><span>{{ session('success') }}</span><button class="icon-btn" type="button" data-dismiss aria-label="Tutup"><x-icon name="x" size="16"/></button></div>@endif
            @yield('content')
            <footer class="page-footer"><span><span class="tiny-dot"></span> Workspace pribadi</span><span>CampusFlow © {{ date('Y') }}</span></footer>
        </main>
    </div>
</div>
@stack('dialogs')
@endsection
