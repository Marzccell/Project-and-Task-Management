@extends('layouts.auth')
@section('title', 'Register')
@section('form')
<span class="pill"><span class="tiny-dot"></span> MULAI WORKSPACE</span><h2>Buat akun.</h2><p class="muted">Proyek pertamamu akan otomatis disiapkan.</p>
@if($errors->any())<div class="error-box" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ route('register') }}" class="auth-form">@csrf
    <label>Nama lengkap<input name="name" value="{{ old('name') }}" maxlength="80" required autocomplete="name" placeholder="Nama lengkap" autofocus></label>
    <label>Alamat email<input name="email" type="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email" placeholder="nama@email.com"></label>
    <label>Password<input name="password" type="password" minlength="8" required autocomplete="new-password" placeholder="8+ karakter, huruf besar, kecil, dan angka"></label>
    <label>Konfirmasi password<input name="password_confirmation" type="password" minlength="8" required autocomplete="new-password" placeholder="Ulangi password"></label>
    <button class="btn primary full" type="submit">Buat workspace <x-icon name="arrow" size="18"/></button>
</form>
<p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
@endsection
