# SiPustaka HMVC React — Panduan Setup

Kode ini mengikuti Modul 9 (Tahap A–F) dan Tugas Praktikum no. 1.

| Modul | Jenis | Akses |
|---|---|---|
| Buku | CRUD Inertia (`useForm`), pencarian + pagination | Lihat: admin & user. Tambah/ubah/hapus: admin |
| Peminjaman | CRUD AJAX (`fetch`) + upload berkas | admin & user |
| UserManagement | CRUD Inertia | admin saja |

## Langkah

```bash
# 1. Buat project (pilih: React, Laravel's built-in authentication, Pest, Yes npm install)
laravel new sipustaka_hmvc_react
cd sipustaka_hmvc_react

# 2. Buat database db_sipustaka di phpMyAdmin, lalu edit .env:
#    APP_LOCALE=id
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=db_sipustaka
#    DB_USERNAME=root
#    DB_PASSWORD=

# 3. Pasang nwidart/laravel-modules
composer config allow-plugins.wikimedia/composer-merge-plugin true
composer require nwidart/laravel-modules
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"

# 4. Salin folder "kit" ini ke root project, lalu patch composer.json (merge-plugin)
python kit/install.py pre
composer dump-autoload

# 5. Buat 3 modul
php artisan module:make Buku Peminjaman UserManagement
composer dump-autoload

# 6. Salin semua file + patch app.tsx, app.css, tsconfig, middleware, bootstrap/app.php
python kit/install.py post

# 7. Migrasi, seed, storage link
php artisan storage:link
php artisan migrate:fresh --seed

# 8. Jalankan
composer run dev      # buka http://127.0.0.1:8000

# 9. Tes otomatis
php artisan test
```

Akun seeder: `admin@example.com / password` (admin), `user@example.com / password` (user).

## Jika patch otomatis gagal (ditandai [MANUAL])
Lakukan manual sesuai modul: app.tsx (Tahap A.9), app.css (A.10), tsconfig (A.11),
HandleInertiaRequests flash (B.9), alias middleware (C.2).

## Bukti untuk laporan
- 403: login sebagai user, buka `/admin/users`.
- Network: DevTools → Network → Fetch/XHR di halaman `/peminjaman`.
- `dir storage\app\public\berkas_peminjaman` dan kolom `berkas` di tabel `peminjamans` (phpMyAdmin).
- `php artisan route:list --except-vendor` dan `php artisan module:list`.
