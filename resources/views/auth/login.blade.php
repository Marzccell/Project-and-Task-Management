@extends('layouts.auth')
@section('title', 'Login')
@section('form')
<span class="pill"><span class="tiny-dot"></span> WORKSPACE PRIBADI</span><h2>Selamat datang kembali.</h2><p class="muted">Masuk untuk melanjutkan proyek dan tugasmu.</p>
@if($errors->any())<div class="error-box" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login') }}" class="auth-form">@csrf
    <label>Alamat email<input name="email" type="email" value="{{ old('email') }}" maxlength="255" placeholder="nama@email.com" required autocomplete="email" autofocus></label>
    <label>Password<input name="password" type="password" placeholder="Masukkan password" required autocomplete="current-password"></label>
    <label class="checkbox-label"><input name="remember" type="checkbox" value="1"> Tetap masuk</label>
    <button class="btn primary full" type="submit">Masuk <x-icon name="arrow" size="18"/></button>
</form>
<p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Buat akun</a></p>
@if(app()->environment('local'))<div class="demo-box"><x-icon name="spark"/><div><strong>Akun demo</strong><p>Gunakan akun contoh untuk mencoba aplikasi.</p><code>demo@campusflow.test</code><br><code>CampusFlow123!</code></div></div>@endif
@endsection
