# CampusFlow

CampusFlow adalah aplikasi **Project & Task Management** berbasis Laravel 12. Aplikasi ini dikembangkan dari personal to-do list menjadi workspace lengkap untuk mengelola project, tugas, deadline, status, dan lampiran secara aman per pengguna.

## Fitur

- Autentikasi berbasis pola standar Laravel Breeze: register, login, remember me, logout, session regeneration, dan rate limiting.
- Dashboard utama dengan statistik project, tugas aktif, tugas selesai, overdue, dan progress keseluruhan.
- CRUD project: nama, deskripsi, deadline, dan status (`Planning`, `Active`, `On Hold`, `Completed`).
- CRUD task di dalam project: judul, deskripsi, deadline, dan status (`To-do`, `In Progress`, `Done`).
- Pencarian tugas berdasarkan judul/deskripsi dan filter gabungan berdasarkan project, status, deadline preset, serta rentang tanggal.
- Upload maksimal 5 lampiran sekaligus dengan batas **10 MB per file**; file disimpan privat dan hanya dapat diakses pemilik tugas.
- Tampilan list dan Kanban dengan drag-and-drop untuk mengubah status tugas.
- Dark mode yang tersimpan di browser.
- Ekspor hasil filter tugas ke file Excel-compatible `.xls`.
- Validasi berlapis di frontend (HTML5 + JavaScript) dan backend (Laravel Form Request).
- Responsive layout untuk desktop, tablet, dan mobile.
- Isolasi data: seluruh query project, task, dan lampiran dibatasi ke user yang sedang login.

## Teknologi

- PHP 8.2+
- Laravel 12 (memenuhi syarat Laravel 10 atau lebih baru)
- Laravel Breeze 2.4
- SQLite secara default
- Blade, vanilla JavaScript, dan custom responsive CSS
- PHPUnit 11

## Relasi Database

```text
users 1 ─── n projects 1 ─── n tasks 1 ─── n task_attachments
  │                              │
  └──────────────────────────────┘
          task ownership
```

`tasks.user_id` dipertahankan untuk ownership check langsung, sementara `tasks.project_id` membentuk pengelompokan tugas ke project. Penghapusan project membersihkan task, metadata lampiran, dan file fisiknya.

## Instalasi Lokal

Persyaratan: PHP 8.2+, Composer, dan ekstensi SQLite PHP.

```bash
composer install
php setup.php
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Akun demo lokal:

```text
Email: demo@campusflow.test
Password: CampusFlow123!
```

Alternatif setup manual:

```bash
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Untuk produksi, ubah `APP_ENV=production`, `APP_DEBUG=false`, konfigurasi database, lalu jalankan:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Lampiran memakai disk `local` dan diunduh melalui controller terautentikasi, sehingga `php artisan storage:link` tidak diperlukan.

## Menjalankan Test

```bash
php artisan test
```

Test mencakup autentikasi, regresi bug login, ownership antar-user, CRUD project/task, validasi project, upload dan batas lampiran, filter gabungan, endpoint drag-and-drop, ekspor Excel, serta cascade cleanup.

## Struktur Penting

```text
app/
├── Http/Controllers/
│   ├── Auth/
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── TaskController.php
│   └── TaskAttachmentController.php
├── Http/Requests/
│   ├── Auth/LoginRequest.php
│   ├── ProjectRequest.php
│   └── TaskRequest.php
└── Models/
    ├── Project.php
    ├── Task.php
    ├── TaskAttachment.php
    └── User.php
```

## Catatan Deployment

Repository ini menyertakan konfigurasi Vercel menggunakan PHP 8.2 community runtime. Local development tetap memakai SQLite, sedangkan deployment Vercel harus memakai PostgreSQL persisten melalui `DB_CONNECTION=pgsql` dan `DB_URL`/`POSTGRES_URL`. Vercel hanya menyediakan filesystem sementara pada `/tmp`, sehingga lampiran pada deployment demo tidak persisten sampai S3-compatible object storage dikonfigurasi.

Environment variable minimum untuk Vercel:

```env
APP_NAME=CampusFlow
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://nama-project.vercel.app
DB_CONNECTION=pgsql
DB_URL=postgresql://...
```

Jalankan migration production setelah database persisten terhubung. Jangan gunakan SQLite untuk deployment Vercel karena perubahan data tidak akan persisten.

## Security Notes

- Password di-hash otomatis melalui cast `hashed` pada model User.
- Login dinormalisasi ke lowercase, dibatasi rate limiter, dan session ID diregenerasi.
- Semua mutasi menggunakan CSRF protection.
- ID project pada task divalidasi harus milik user yang login.
- Lampiran tidak diekspos langsung dari folder public.
- Nama/isi yang ditampilkan di Blade di-escape secara default.

## License

MIT
