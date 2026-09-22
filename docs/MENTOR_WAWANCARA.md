# Persiapan Demo dan Wawancara CampusFlow

Dokumen ini membantu menjelaskan aplikasi Project & Task Management yang sekarang sudah sesuai requirement teknis.

## Demo 3 Menit

1. **Login dan dashboard (30 detik).** Masuk dengan akun demo. Tunjukkan statistik project, task, progress, dan overdue. Jelaskan bahwa bug login lama sebenarnya terjadi saat halaman tujuan gagal merender; sekarang login diarahkan ke dashboard yang sudah diuji.
2. **CRUD project (40 detik).** Buat project baru berisi nama, deskripsi, deadline, dan status. Edit statusnya dari Planning ke Active.
3. **CRUD task (50 detik).** Buka project, buat task, pilih deadline/status, dan unggah PDF kecil. Edit task lalu download lampiran.
4. **Filter dan interaksi (35 detik).** Cari task, kombinasikan filter project + status + deadline, buka Board, lalu drag kartu dari To-do ke In Progress.
5. **Nilai plus (25 detik).** Aktifkan dark mode dan ekspor hasil filter ke Excel.

## Alur Data

```text
User -> Project -> Task -> TaskAttachment
  \----------------> Task (ownership langsung)
```

- Satu user mempunyai banyak project.
- Satu project mempunyai banyak task.
- Satu task mempunyai banyak attachment.
- `tasks.user_id` membuat ownership check langsung dan `tasks.project_id` mengelompokkan task.

## Alur Create Task

1. Browser mengirim `POST /tasks` dengan CSRF token dan session cookie.
2. Middleware `auth` memastikan user sudah login.
3. `TaskRequest` memvalidasi project, teks, status, tanggal, tipe file, dan batas 10 MB.
4. Rule project memastikan `project_id` benar-benar milik user login.
5. Controller membuat task lewat relasi `$request->user()->tasks()`.
6. Attachment disimpan pada private local disk dan metadata disimpan di database.
7. Controller mengembalikan redirect dengan flash message.

## Poin Engineering yang Perlu Bisa Dijelaskan

### Authentication vs authorization

Authentication menjawab “siapa yang login”, sedangkan authorization menjawab “data mana yang boleh diakses”. Breeze menangani pola login/register/session; controller selalu memulai query dari relasi user agar ID milik pengguna lain menghasilkan 404.

### Mengapa lampiran tidak di `public/storage`?

Lampiran bersifat privat. Download harus melewati controller terautentikasi yang memeriksa ownership task. URL file langsung dapat melewati pemeriksaan tersebut, sehingga file tidak diekspos publik.

### Mengapa validasi frontend dan backend?

HTML5/JavaScript memberi feedback cepat, tetapi request dapat dikirim tanpa form browser. Form Request Laravel tetap menjadi sumber kebenaran dan menolak status, project, tanggal, ukuran, atau tipe file yang tidak valid.

### Bagaimana drag-and-drop bekerja?

JavaScript memindahkan kartu secara optimistis lalu mengirim `PATCH /tasks/{id}/status` sebagai JSON dengan CSRF token. Jika server gagal, kartu dikembalikan ke kolom awal. Endpoint tetap memeriksa task melalui relasi user.

### Bagaimana bug login diperbaiki?

Kredensial sebelumnya berhasil, tetapi redirect ke `/tasks` memicu parse error Blade saat task tersedia. Tampilan task dibangun ulang dengan struktur directive Blade yang valid, login memakai `LoginRequest` pola Breeze, session diregenerasi, dan tujuan default dipindahkan ke dashboard. Test regresi memverifikasi dashboard dan task page sama-sama merender setelah login.

### Mengapa SQLite?

SQLite membuat demo lokal mudah dijalankan. Untuk Railway/produksi, gunakan PostgreSQL atau MySQL karena filesystem dan database SQLite pada instance ephemeral tidak cocok untuk data persisten multi-user.

## Test Otomatis

Jalankan:

```bash
php artisan test
```

Coverage fitur meliputi register/login, regresi login, CRUD project/task, isolasi antar-user, project ownership, lampiran dan batas 10 MB, filter deadline, status JSON untuk drag-and-drop, ekspor Excel, serta cleanup file ketika project dihapus.

## Batasan yang Jujur

- Belum ada kolaborasi team, assignment anggota, notifikasi, reset password, dan email verification.
- File memakai local disk; produksi multi-instance sebaiknya memakai S3-compatible object storage.
- Export memakai format HTML Excel-compatible `.xls`, bukan formula workbook kompleks.
- Board memuat maksimal 60 task per halaman agar UI tetap ringan.

## Checklist Sebelum Pengumpulan

- [ ] Jalankan `composer install` dan `php setup.php` dari clone bersih.
- [ ] Jalankan seluruh test.
- [ ] Coba register, login, logout, dan akun demo.
- [ ] Coba CRUD project dan task.
- [ ] Uji file tepat di bawah 10 MB dan file di atas 10 MB.
- [ ] Uji filter, Board drag-and-drop, dark mode, dan export.
- [ ] Set repository GitHub ke Public.
- [ ] Jangan commit `.env`, database SQLite, log, atau folder `vendor`.
- [ ] Jika ada demo, tambahkan URL dan kredensial demo ke README.

Jika ditanya penggunaan AI, jawab sesuai kenyataan dan fokuskan penjelasan pada bagian yang sudah dipahami, keputusan teknis, test, serta perbaikan yang bisa dilakukan sendiri.
