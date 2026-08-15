# Manual Deployment Guide

Panduan ini menggunakan PHP, Apache/Nginx, Node.js, dan MySQL/MariaDB langsung — tanpa Docker.

## 1. Prasyarat server

- PHP 8.2+ dengan extension `intl`, `mysqli`, dan `zip`.
- Composer 2, Node.js 20+, dan MySQL 8/MariaDB 10.6+.
- Web server dengan HTTPS aktif.

## 2. Backend API

1. Salin `backend-ci4/.env.example` menjadi `backend-ci4/.env` dan isi konfigurasi produksi. Gunakan `CI_ENVIRONMENT = production`, kredensial database produksi, JWT secret acak yang panjang, `app.baseURL` HTTPS, serta `CORS_ALLOWED_ORIGIN` domain frontend.
2. Dari `backend-ci4`, jalankan:

   ```powershell
   composer install --no-dev --optimize-autoloader
   php spark migrate --all --no-interaction
   php spark db:seed BookingDemoSeeder # hanya untuk environment demo
   ```

3. Arahkan document root web server ke `backend-ci4/public`. Aktifkan rewrite agar request API tetap melewati `public/index.php`.
4. Pastikan folder `backend-ci4/writable` dapat ditulis oleh user web server.

## 3. Frontend

Build frontend harus memakai URL API HTTPS yang benar:

```powershell
Set-Location frontend-react
$env:VITE_API_URL = 'https://api.example.com/api'
npm ci
npm run build
```

Upload seluruh isi `frontend-react/dist` ke document root frontend. Konfigurasikan fallback SPA: request file yang tidak ada harus kembali ke `index.html`.

## 4. Smoke test setelah rilis

1. Buka frontend melalui HTTPS dan login dengan akun admin.
2. Pastikan `GET /api/auth/me` merespons 200 dengan token valid.
3. Buat satu booking, lakukan approval Level 1 dan Level 2, lalu tandai selesai.
4. Buka Laporan dan ekspor Excel; cek Activity Log merekam `REPORT_EXPORTED`.
5. Pastikan response API tidak mengirim `Access-Control-Allow-Origin` untuk domain yang tidak diizinkan.

## 5. Catatan keamanan

- Jangan commit `backend-ci4/.env` atau secret JWT/database.
- Gunakan HTTPS dan password database unik.
- Ganti akun demo sebelum digunakan di luar assessment.
