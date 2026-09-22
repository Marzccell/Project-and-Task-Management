@extends('layouts.base')
@section('body')
<main class="auth-shell">
    <section class="auth-story"><a class="brand" href="{{ url('/') }}"><span class="brand-mark"><x-icon name="check" size="23"/></span>CampusFlow<span class="brand-dot">.</span></a><div class="auth-copy"><span class="eyebrow">PROYEK DAN TUGAS DALAM SATU TEMPAT</span><h1>Kerja lebih rapi.<br>Prioritas lebih jelas.<br><em>Progres terlihat.</em></h1><p>Atur proyek, kelola deadline, dan selesaikan setiap tugas<br>tanpa kehilangan gambaran besarnya.</p></div><div class="auth-art" aria-hidden="true"><div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div><div class="art-card"><span class="art-check">✓</span><div>Satu langkah berikutnya.<small>Tetap fokus pada yang penting.</small></div><span class="art-star">✦</span></div></div><div class="auth-foot"><span>CAMPUSFLOW WORKSPACE</span><span>01 / ∞</span></div></section>
    <section class="auth-form-side"><div class="auth-form-wrap">@yield('form')</div><p class="auth-note">Proyek rapi, progres mudah dipantau.</p></section>
</main>
@endsection
