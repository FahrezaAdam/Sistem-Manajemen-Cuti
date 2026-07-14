# API Sistem Manajemen Cuti

**Technical Test Submission**
- **Nama Pelamar**: Fahreza Adam Nuardiansyah
- **Posisi**: Backend Developer 

---

## Ringkasan Eksekutif (Untuk Tim HR & Recruiter)
Repository ini merupakan hasil pengerjaan *Technical Test* untuk seleksi Magang Backend Developer. Proyek ini adalah sebuah RESTful API untuk Sistem Manajemen Cuti Karyawan yang telah dikerjakan dengan memenuhi **seluruh (100%)** kriteria wajib dari soal tes:
- Framework menggunakan **Laravel 11**.
- Implementasi Otentikasi Ganda: Login konvensional & **Google OAuth 2.0**.
- Manajemen *Role* yang ketat (Admin & Employee).
- Logika pemotongan otomatis batas limit cuti (12 hari per tahun).
- Unggah file bukti lampiran cuti.

Untuk pengujian *endpoint* API, Anda dapat menggunakan dua fasilitas dokumentasi berikut:
1. **[Postman Collection (Publik)](https://documenter.getpostman.com/view/53167638/2sBY4LSNVm)**: Dapat diakses langsung tanpa perlu instalasi lokal.
2. **Swagger UI**: Dokumentasi API interaktif bawaan (Dapat diakses di `http://localhost:8000/api/documentation` setelah server lokal dijalankan).

**Video Demo Sistem**: [Tonton Video Demonstrasi di Sini](https://youtu.be/PinrqS507nk)

---

## Panduan Instalasi (Untuk Tim Engineer / Reviewer)

### Prasyarat Instalasi
- PHP 8.2 atau lebih tinggi
- Composer
- MySQL atau PostgreSQL

### Langkah Instalasi
1. Clone repository:
```bash
git clone https://github.com/FahrezaAdam/Sistem-Manajemen-Cuti.git
cd FahrezaAdamNuardiansyah-BackendTechnicalTest
composer install
```

2. Konfigurasi Environment:
```bash
cp .env.example .env
php artisan key:generate
```
Sesuaikan koneksi database di `.env` (MySQL port 3306 digunakan secara default).
*(Untuk mengetes login Google OAuth, pastikan mengisi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET`).*

3. Jalankan Migrasi dan Symlink:
```bash
php artisan migrate
php artisan storage:link
```

4. Jalankan Server:
```bash
php artisan serve
```

---

## Alur Sistem & Arsitektur (Technical Overview)

Untuk memenuhi standar pengembangan perangkat lunak yang *scalable* dan *maintainable*, sistem ini dibangun dengan mengimplementasikan prinsip-prinsip *Clean Architecture* dalam ekosistem Laravel. Berikut adalah penjabaran detail mengenai arsitektur dan alur sistem yang diterapkan:

### 1. Request Lifecycle (Alur Eksekusi Permintaan)
Setiap *request* HTTP yang masuk (khususnya untuk *endpoint* krusial seperti pengajuan cuti) harus melewati lapisan inspeksi yang ketat sebelum menyentuh *Controller*:
`Route -> Auth Middleware (Sanctum) -> Role Middleware (IsAdmin) -> FormRequest Validation -> Controller -> Eloquent ORM -> Database Response`

### 2. Separation of Concerns (Pemisahan Logika)
- **Fat Request, Skinny Controller**: Seluruh logika validasi data (tipe file, logika kalender *start/end date*) dan otorisasi *user* diekstraksi keluar dari Controller, ditempatkan ke dalam kelas *Dependency Injection* `FormRequest` khusus (contoh: `StoreLeaveRequest.php`). Hal ini menjaga Controller tetap bersih (*clean*) dan murni hanya berfokus pada eksekusi bisnis logika serta memformat JSON *response*.

### 3. Arsitektur Database & Integritas Relasional
- Sistem menggunakan dua entitas utama: `users` dan `leaves`.
- **Referential Integrity**: Tabel `leaves` berelasi *belongsTo* ke tabel `users`. Constraint *Foreign Key* (`cascadeOnDelete`) diterapkan pada level *migration* basis data. Dengan begitu, jika profil seorang karyawan dihapus, seluruh rekam jejak cutinya akan ikut terhapus otomatis secara *native* oleh database tanpa meninggalkan data yatim (*orphaned records*).
- Kolom `role` menggunakan tipe data `enum` murni dan dijaga menggunakan validasi `Rule::in()` di sisi aplikasi untuk memblokir celah serangan *Mass-Assignment Vulnerability*.

### 4. Alur Bisnis (Event-Driven Quota Logic)
Alur pemotongan dan pengembalian (*refund*) kuota dirancang agar sangat kedap terhadap kesalahan matematis:
1. Ketika pengajuan lolos validasi, sistem memanfaatkan *Carbon* untuk menghitung durasi rentang tanggal yang di- *request*.
2. Sistem mengecek *state* `leave_quota` dari model. Jika jumlah durasi melebihi sisa kuota, sistem membatalkan proses dengan error 400 *Bad Request*.
3. Pada saat pengajuan dibuat, status akan diset menjadi *Pending*. Pada tahap ini **kuota cuti belum dipotong**.
4. Ketika *Admin* melakukan eksekusi persetujuan (*Approve*), barulah sistem memeriksa apakah kuota karyawan masih mencukupi. Jika mencukupi, sistem akan secara resmi **memotong (decrement)** kuota cuti tersebut. Jika *Admin* menolak (*Reject*), kuota karyawan akan tetap utuh tanpa terpotong.

### 5. Strategi Otentikasi Terpusat
- Sistem API ini menggunakan **Laravel Sanctum** untuk menerbitkan token (*Personal Access Tokens*) bagi mekanisme login lokal.
- Untuk login pihak ketiga, **Laravel Socialite** diintegrasikan sebagai *OAuth 2.0 Handler*. Socialite mengelola *callback* dan pertukaran kode otorisasi dari Google, yang kemudian di- *mapping* (dipetakan) ke dalam tabel `users`. Setelah pemetaan berhasil, sistem men- *generate* token Sanctum yang persis sama dengan login lokal. Hasil akhirnya, *Frontend* memiliki mekanisme otorisasi (*Bearer Token*) tunggal dan seragam untuk semua skenario akses.

### 6. Storage Abstraction (Filesystem)
File bukti dokumen pendukung (gambar/pdf) tidak disimpan ke dalam tabel database (untuk menghindari degradasi performa/ *database bloating*). Sistem memanfaatkan *Facade Storage* melalui lapisan abstraksi *Flysystem*. Walaupun *driver* saat ini diarahkan ke *disk* `public` (lokal) agar penguji bisa melakukan tes dengan mudah, kode Controller telah *Cloud-Ready*. *Migration* infrastruktur ke Cloud Storage (seperti AWS S3) kelak hanya membutuhkan satu kali penyesuaian di variabel `.env`, tanpa perlu menyentuh satu baris pun logika *upload* di Controller.
