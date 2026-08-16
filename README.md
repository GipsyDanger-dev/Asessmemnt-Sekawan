# Vehicle Booking & Approval System

Aplikasi ini dibuat untuk technical assessment Fullstack Developer. Fungsinya adalah membantu pool kendaraan mencatat pemesanan kendaraan, menentukan driver, dan menjalankan persetujuan berjenjang sampai dua level.

Selain booking dan approval, aplikasi juga menyediakan dashboard pemakaian kendaraan, laporan yang dapat diekspor ke Excel, activity log, serta monitoring BBM dan jadwal service.

## Teknologi yang digunakan

- Backend: CodeIgniter 4
- Frontend: React + TypeScript + Vite
- Database: MySQL 8
- PHP: 8.2 atau lebih baru
- Autentikasi: JWT

## Akun demo

Semua akun demo menggunakan password yang sama: `Password123!`

| Nama akun | Peran | Email |
|---|---|---|
| Admin Pool | Membuat dan mengelola booking | `admin@vehicle.test` |
| Manager Vehicle | Approver Level 1 | `manager@vehicle.test` |
| Director Vehicle | Approver Level 2 | `director@vehicle.test` |

Alur yang disarankan untuk mencoba aplikasi:

1. Login sebagai **Admin Pool** dan buat pemesanan kendaraan.
2. Login sebagai **Manager Vehicle** untuk menyetujui Level 1.
3. Login sebagai **Director Vehicle** untuk menyetujui Level 2.
4. Kembali ke akun admin untuk melihat perubahan status, laporan, dan activity log.

## Menjalankan aplikasi secara lokal

### 1. Siapkan database

Buat database MySQL bernama `vehicle_booking`, atau import file `vehicle_booking.sql` jika file dump database disertakan bersama ZIP.

Kemudian salin konfigurasi environment:

```powershell
Copy-Item backend-ci4/.env.example backend-ci4/.env
```

Sesuaikan bagian database dan `JWT_SECRET` di file `backend-ci4/.env`.

### 2. Jalankan backend

```powershell
cd backend-ci4
composer install
php spark migrate --all
php spark db:seed BookingDemoSeeder
php spark serve
```

Backend akan tersedia di `http://localhost:8080`.

### 3. Jalankan frontend

Buka terminal baru:

```powershell
cd frontend-react
npm install
npm run dev
```

Frontend biasanya tersedia di `http://localhost:5173`.

## Fitur utama

- Admin membuat booking, memilih kendaraan, driver, serta approver Level 1 dan Level 2.
- Approval dilakukan berjenjang melalui akun approver.
- Dashboard menampilkan ringkasan booking dan pemakaian kendaraan.
- Laporan booking dapat difilter dan diekspor ke Excel.
- Fleet Monitoring mencatat riwayat pemakaian, konsumsi BBM, serta jadwal service kendaraan.
- Activity Log menyimpan proses penting dalam aplikasi.
- Hak akses dipisahkan: admin mengelola operasional, approver hanya melakukan persetujuan.

## Dokumen pendukung

- [PRD](PRD.md)
- [Physical Data Model](docs/PDM.md)
- [Activity Diagram](docs/activity-diagram.md)
- [Panduan deployment manual](DEPLOYMENT.md)

Project ini tidak menggunakan Docker. Semua layanan dijalankan langsung menggunakan PHP, Node.js, dan MySQL.
