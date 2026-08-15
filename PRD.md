# PRD — Vehicle Booking & Approval System

  ## 1. Ringkasan Produk

  Vehicle Booking & Approval System adalah aplikasi web untuk mengelola pemesanan kendaraan operasional perusahaan
  tambang yang tersebar di beberapa region. Aplikasi membantu admin pool kendaraan mencatat pemesanan, menetapkan
  kendaraan dan driver, serta mengirim permintaan persetujuan berjenjang kepada atasan terkait.

  Sistem juga menyediakan dashboard pemakaian kendaraan, laporan periodik yang dapat diekspor ke Excel, dan audit log
  untuk setiap proses penting.

  ## 2. Tujuan

  - Menghindari bentrok jadwal penggunaan kendaraan.
  - Memastikan setiap pemakaian kendaraan melalui proses persetujuan minimal dua level.
  - Mempermudah admin mengelola kendaraan, driver, dan booking.
  - Memberikan visibilitas penggunaan kendaraan melalui dashboard dan laporan.
  - Menunjukkan kemampuan implementasi PHP 8, CodeIgniter 4, React, REST API, JWT, dan MySQL sesuai requirement posisi.

  ## 3. Target Pengguna dan Role

   Role          Deskripsi                                 Hak Akses
  ━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Admin Pool    Pengelola kendaraan                       Kelola master kendaraan/driver, membuat booking, menetapkan
                                                           kendaraan dan driver, melihat seluruh booking, laporan,
                                                           dashboard
  ────────────  ────────────────────────────────────────  ──────────────────────────────────────────────────────────────
   Approver      Pihak atasan yang menyetujui pemakaian    Melihat booking yang menunggu persetujuannya, approve/
                                                           reject, melihat riwayat approval sendiri

  Catatan: Secara role hanya ada dua kategori user: admin dan approver. Namun untuk memenuhi approval minimal dua level,
  approver memiliki atribut approval_level, misalnya Level 1 dan Level 2.

  Akun demo minimal:

  Admin Pool       : admin@vehicle.test
  Approver Level 1 : manager@vehicle.test
  Approver Level 2 : director@vehicle.test

  ## 4. Ruang Lingkup MVP

  ### 4.1 Authentication dan Authorization

  - Login menggunakan email dan password.
  - Backend menghasilkan JWT access token.
  - Endpoint privat hanya dapat diakses dengan JWT valid.
  - Role-based access control:
      - Admin tidak dapat melakukan approval atas nama approver.
      - Approver tidak dapat membuat atau mengubah booking.
      - Approver hanya dapat mengakses booking yang memang dialokasikan kepadanya.

  - Logout di sisi frontend dengan menghapus token sesi.

  ### 4.2 Master Data

  Admin dapat mengelola:

  - Region/lokasi operasional.
  - Kendaraan.
  - Driver.
  - User/approver.
  - Konfigurasi urutan approval.

  Data kendaraan minimal:

  Kode kendaraan
  Nomor polisi
  Jenis kendaraan
  Kategori: angkutan orang / angkutan barang
  Status kepemilikan: perusahaan / sewa
  Region
  Status operasional: tersedia / maintenance / tidak aktif

  Data driver minimal:

  Nama
  Nomor telepon
  Nomor SIM
  Masa berlaku SIM
  Region
  Status aktif

  ### 4.3 Pemesanan Kendaraan

  Admin membuat booking berdasarkan permintaan pegawai. Field booking:

  Nomor booking otomatis
  Nama pemohon
  NIK pemohon
  Departemen
  Region
  Tujuan perjalanan
  Keperluan perjalanan
  Tanggal dan waktu mulai
  Tanggal dan waktu selesai
  Jumlah penumpang
  Kategori kendaraan yang dibutuhkan
  Kendaraan yang dipilih
  Driver yang ditetapkan
  Approver level 1
  Approver level 2
  Catatan

  Aturan bisnis:

  - Waktu selesai harus lebih besar dari waktu mulai.
  - Kendaraan dan driver harus aktif.
  - Kendaraan tidak dapat dipilih apabila sedang digunakan pada jadwal yang bertabrakan.
  - Driver tidak dapat ditetapkan bila memiliki penugasan lain pada periode yang sama.
  - Booking baru berstatus PENDING_LEVEL_1.
  - Kendaraan berstatus MAINTENANCE tidak dapat digunakan untuk booking.

  ### 4.4 Approval Berjenjang

  Alur status:

  DRAFT
    ↓
  PENDING_LEVEL_1
    ↓ approve L1
  PENDING_LEVEL_2
    ↓ approve L2
  APPROVED
    ↓ setelah pemakaian selesai
  COMPLETED

  Alur penolakan:

  PENDING_LEVEL_1 / PENDING_LEVEL_2
    ↓ reject
  REJECTED

  Ketentuan:

  - Approver Level 1 hanya dapat approve/reject pada tahap Level 1.
  - Setelah Level 1 menyetujui, booking diteruskan ke Level 2.
  - Setelah Level 2 menyetujui, booking menjadi APPROVED.
  - Reject wajib menyertakan alasan.
  - Setelah reject, booking tidak dapat diteruskan tanpa dibuat ulang atau direvisi admin.
  - Riwayat approval menyimpan approver, level, keputusan, waktu, dan catatan.

  ### 4.5 Dashboard

  Dashboard admin menampilkan:

  - Total booking pada periode terpilih.
  - Jumlah booking pending, approved, rejected, dan completed.
  - Jumlah kendaraan tersedia vs sedang digunakan.
  - Grafik jumlah booking per bulan.
  - Grafik pemakaian kendaraan berdasarkan kategori.
  - Daftar lima kendaraan dengan frekuensi pemakaian tertinggi.
  - Daftar booking yang membutuhkan tindakan atau akan dimulai dalam waktu dekat.

  Filter dashboard:

  Periode
  Region
  Jenis kendaraan

  ### 4.6 Laporan dan Export Excel

  Admin dapat melihat laporan booking secara periodik dengan filter:

  Tanggal mulai dan selesai
  Region
  Kendaraan
  Status booking
  Jenis kendaraan

  Kolom laporan:

  Nomor booking
  Pemohon
  Departemen
  Tujuan
  Kendaraan
  Driver
  Waktu mulai
  Waktu selesai
  Status
  Approver Level 1
  Approver Level 2

  Fitur:

  - Pagination.
  - Pencarian berdasarkan nomor booking, pemohon, atau nomor polisi.
  - Export hasil filter aktif ke .xlsx.
  - Nama file export mengikuti pola:

  vehicle-booking-report-YYYYMMDD-YYYYMMDD.xlsx

  ### 4.7 Activity Log

  Sistem mencatat aktivitas penting:

  - Login berhasil/gagal.
  - Booking dibuat, diubah, atau dibatalkan.
  - Kendaraan atau driver ditetapkan/diubah.
  - Approval dan rejection.
  - Export laporan.
  - Perubahan status kendaraan.

  Struktur log minimal:

  User
  Aksi
  Modul
  Entity ID
  Deskripsi
  Timestamp
  IP address opsional

  ## 5. User Flow

  ### Booking dan Approval

  Admin login
    ↓
  Admin membuat booking
    ↓
  Sistem validasi jadwal kendaraan dan driver
    ↓
  Admin memilih approver Level 1 dan Level 2
    ↓
  Booking dikirim ke Level 1
    ↓
  Level 1 approve/reject
    ↓
  Jika approve → dikirim ke Level 2
    ↓
  Level 2 approve/reject
    ↓
  Jika approve → booking disetujui
    ↓
  Kendaraan siap digunakan
    ↓
  Admin menandai pemakaian selesai

  ## 6. Halaman Frontend

   Halaman             Pengguna          Fungsi
  ━━━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Login               Semua user        Autentikasi pengguna
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Dashboard           Admin             Statistik dan grafik pemakaian
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Daftar Booking      Admin             Melihat, filter, cari, dan kelola booking
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Form Booking        Admin             Membuat atau mengubah booking
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Detail Booking      Admin/Approver    Informasi booking dan timeline approval
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Approval Inbox      Approver          Daftar booking yang memerlukan tindakan
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Riwayat Approval    Approver          Riwayat keputusan approval
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Kendaraan           Admin             Kelola master kendaraan
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Driver              Admin             Kelola master driver
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Laporan             Admin             Filter laporan dan export Excel
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Activity Log        Admin             Audit aktivitas aplikasi
  ──────────────────  ────────────────  ───────────────────────────────────────────
   Profile             Semua user        Informasi akun dan logout

  ## 7. Desain UI/UX

  Prinsip utama:

  - Responsif untuk desktop, tablet, dan mobile.
  - Sidebar navigasi pada desktop, drawer pada mobile.
  - Status booking menggunakan badge warna yang konsisten:
      - Pending: kuning.
      - Approved: hijau.
      - Rejected: merah.
      - Completed: biru/abu-abu.

  - Tabel mendukung pagination, filter, search, dan empty state.
  - Form booking dibagi menjadi section agar tidak terasa penuh:
      1. Informasi pemohon.
      2. Jadwal dan tujuan perjalanan.
      3. Kendaraan dan driver.
      4. Approval.

  - Detail booking menampilkan timeline yang jelas: dibuat → approval L1 → approval L2 → selesai/reject.
  - Konfirmasi modal untuk aksi kritis seperti reject, pembatalan, dan perubahan kendaraan.

  ## 8. Arsitektur Teknis

  ### Backend

  PHP 8.2+
  CodeIgniter 4
  MySQL 8
  REST API
  JWT authentication
  CodeIgniter validation
  Database migrations dan seeders

  Struktur backend yang disarankan:

  app/
  ├─ Controllers/Api/
  ├─ Models/
  ├─ Services/
  ├─ Filters/
  ├─ Entities/
  ├─ Database/Migrations/
  ├─ Database/Seeds/
  └─ Config/

  Prinsip backend:

  - Controller menangani request/response.
  - Service menangani aturan bisnis seperti konflik jadwal dan workflow approval.
  - Model menangani akses database.
  - Filter menangani JWT dan pemeriksaan role.
  - API memakai response JSON yang konsisten.
  - Validasi dilakukan di backend, bukan hanya frontend.

  ### Frontend

  React
  Vite
  React Router
  Axios
  TanStack Query atau Context API
  Chart.js / Recharts
  Tailwind CSS atau Material UI

  Prinsip frontend:

  - Halaman privat dilindungi ProtectedRoute.
  - Token API dikirim melalui interceptor Axios.
  - State server seperti daftar booking dan dashboard memakai TanStack Query bila memungkinkan.
  - Error, loading, dan empty state harus jelas.
  - Form memiliki validasi dasar sebelum request dikirim.

  ## 9. REST API Utama

  ### Auth

  POST /api/auth/login
  GET  /api/auth/me
  POST /api/auth/logout

  ### Booking

  GET    /api/bookings
  POST   /api/bookings
  GET    /api/bookings/{id}
  PUT    /api/bookings/{id}
  DELETE /api/bookings/{id}
  POST   /api/bookings/{id}/complete

  ### Approval

  GET  /api/approvals/inbox
  POST /api/bookings/{id}/approve
  POST /api/bookings/{id}/reject
  GET  /api/bookings/{id}/approval-history

  ### Master Data

  GET/POST/PUT/DELETE /api/vehicles
  GET/POST/PUT/DELETE /api/drivers
  GET/POST/PUT/DELETE /api/regions
  GET/POST/PUT/DELETE /api/users

  ### Dashboard, Reports, dan Logs

  GET /api/dashboard/summary
  GET /api/dashboard/vehicle-usage
  GET /api/reports/bookings
  GET /api/reports/bookings/export
  GET /api/activity-logs

  ## 10. Physical Data Model

  Tabel inti:

  users
  roles
  regions
  vehicles
  drivers
  vehicle_bookings
  booking_approvals
  vehicle_usage_logs
  activity_logs

  Relasi utama:

  regions 1 --- * vehicles
  regions 1 --- * drivers
  users 1 --- * vehicle_bookings        (admin pembuat)
  vehicles 1 --- * vehicle_bookings
  drivers 1 --- * vehicle_bookings
  vehicle_bookings 1 --- * booking_approvals
  users 1 --- * booking_approvals       (approver)
  users 1 --- * activity_logs

  Detail penting tabel vehicle_bookings:

  id
  booking_number
  requester_name
  requester_nik
  department
  region_id
  vehicle_id
  driver_id
  purpose
  destination
  start_at
  end_at
  passenger_count
  status
  created_by
  notes
  created_at
  updated_at

  Detail penting tabel booking_approvals:

  id
  booking_id
  approver_id
  approval_level
  status
  approved_at
  rejected_at
  remarks

  ## 11. Kebutuhan Non-Fungsional

  - API menggunakan HTTPS saat deployment.
  - Password disimpan dengan hashing bawaan PHP, misalnya bcrypt/Argon2.
  - JWT memiliki masa berlaku.
  - Endpoint divalidasi dan dilindungi role middleware.
  - Semua query memakai ORM/query builder untuk mengurangi risiko SQL injection.
  - Password, JWT secret, dan konfigurasi database disimpan di .env, tidak di-commit.
  - Tampilan minimum nyaman di lebar layar 320px.
  - Error API tidak boleh membocorkan detail teknis atau database.

  ## 12. Out of Scope MVP

  Agar tidak overengineering, fitur berikut tidak wajib pada versi assessment:

  - Integrasi GPS kendaraan real-time.
  - Monitoring konsumsi BBM.
  - Jadwal dan riwayat service secara penuh.
  - Push notification FCM.
  - Queue/RabbitMQ.
  - Multi-tenant atau SSO perusahaan.
  - Upload dokumen perjalanan.

  Fitur ini dapat disebut sebagai roadmap lanjutan.

  ## 13. Acceptance Criteria

  Aplikasi dianggap selesai apabila:

  - Admin dapat login dan membuat booking.
  - Sistem menolak jadwal kendaraan atau driver yang bertabrakan.
  - Admin dapat menetapkan kendaraan, driver, serta dua approver.
  - Approver Level 1 dapat approve/reject booking.
  - Approver Level 2 hanya menerima booking setelah approval Level 1.
  - Booking menjadi APPROVED hanya setelah kedua level menyetujui.
  - Approver tidak dapat mengakses booking yang bukan tugasnya.
  - Dashboard menampilkan statistik dan minimal dua grafik pemakaian.
  - Laporan dapat difilter dan diekspor ke Excel.
  - Setiap aksi inti tercatat di activity log.
  - README menjelaskan setup, akun demo, stack, versi PHP/MySQL, dan cara penggunaan.
  - Disertakan PDM dan activity diagram.

  ## 14. Deliverables

  Source code backend CodeIgniter 4
  Source code frontend React
  Database migration dan seeder
  File .env.example
  README.md
  Physical Data Model
  Activity Diagram
  Screenshot aplikasi