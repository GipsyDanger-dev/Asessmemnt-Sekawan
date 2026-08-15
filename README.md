# Vehicle Booking & Approval System

Monorepo untuk assessment aplikasi pemesanan kendaraan operasional dengan approval dua level.

## Struktur

- `backend-ci4/` — REST API CodeIgniter 4, MySQL, JWT.
- `frontend-react/` — React, TypeScript, dan Vite.
- `PRD.md` — kebutuhan produk dan acceptance criteria.

## Prasyarat Lokal

- PHP 8.2+ dengan ekstensi `intl` dan `zip`.
- Composer 2.
- MySQL 8.
- Node.js 20+ dan npm.

## Menjalankan Backend

```powershell
Copy-Item backend-ci4/.env.example backend-ci4/.env
cd backend-ci4
composer install
php spark serve
```

Atur kredensial MySQL dan `JWT_SECRET` di `backend-ci4/.env` sebelum menjalankan migration.

## Menjalankan Frontend

```powershell
cd frontend-react
npm install
npm run dev
```

Detail fitur dan alur bisnis tersedia pada [PRD.md](PRD.md).

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin Pool | `admin@vehicle.test` | `Password123!` |
| Approver Level 1 | `manager@vehicle.test` | `Password123!` |
| Approver Level 2 | `director@vehicle.test` | `Password123!` |

## Database dan Seeder

```powershell
cd backend-ci4
php spark migrate --all
php spark db:seed BookingDemoSeeder
```

Dokumen deliverable: [PDM](docs/PDM.md), [activity diagram](docs/activity-diagram.md), dan [screenshot desain login](docs/screenshots/login-v2.png).

## Nilai tambah monitoring armada

Admin dapat membuka **Fleet monitoring** untuk melihat riwayat pemakaian kendaraan, mencatat konsumsi BBM beserta odometer/biaya, serta membuat dan menyelesaikan jadwal service. Semua proses tersebut masuk ke Activity Log dan ringkasannya tampil di dashboard.

## Deployment manual

Gunakan panduan [DEPLOYMENT.md](DEPLOYMENT.md) untuk deploy dengan PHP, Apache/Nginx, Node.js, dan MySQL/MariaDB langsung. Tidak menggunakan Docker.
