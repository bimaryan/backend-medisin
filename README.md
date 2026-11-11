# Medisin - Backend REST API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-red.svg?style=for-the-badge&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8.4-4479A1.svg?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/Security-AES--256--CBC%20%2B%20DB%20Encryption-blue.svg?style=for-the-badge&logo=security" alt="Security Encrypted">
  <img src="https://img.shields.io/badge/PHPUnit-50%20Tests%20169%20Assertions%20Passed-brightgreen.svg?style=for-the-badge&logo=phpunit" alt="PHPUnit">
  <img src="https://img.shields.io/badge/Postman-Collection%20v2.1-orange.svg?style=for-the-badge&logo=postman" alt="Postman">
</p>

Repositori ini berisi implementasi **Backend REST API** untuk sistem informasi rekam medis dan kunjungan pasien (**Medisin**), dibangun menggunakan arsitektur modern berbasis Laravel dan MySQL.

Seluruh implementasi dibangun dengan standar kode industri modern menggunakan **Laravel 13**, **PHP 8.3**, **MySQL**, **Laravel Sanctum**, dan **PHPUnit**, dengan spesifikasi data, validasi, dan alur antarmuka yang presisi mengikuti rancangan **Referensi Figma**, serta dilengkapi fitur **Security Test (Enkripsi Payload & Enkripsi Data Database)**.

---

## 📋 Fitur & Spesifikasi Sistem

| Fitur Sistem | Status | Implementasi & Lokasi File |
| :--- | :---: | :--- |
| **REST API Login** | ✅ **Selesai** | `POST /api/login` mendukung username/user_name & password terenkripsi, Sanctum token ([`AuthController.php`](app/Http/Controllers/Api/AuthController.php)) |
| **Security: Enkripsi Payload** | ✅ **Selesai** | `user_name`, `password`, & `captcha_code` terenkripsi AES-256-CBC di sisi client (Web Crypto) sebelum dikirim ke API; backend mendekripsi otomatis ([`SecurityService.php`](app/Services/SecurityService.php)) |
| **Security: Enkripsi Database** | ✅ **Selesai** | Data `nik` pada tabel `pasiens` dan `email` pada tabel `users` tersimpan terenkripsi di database (*encryption-at-rest*) |
| **Security: Captcha Login** | ✅ **Selesai** | `GET /api/captcha` menghasilkan gambar captcha PNG (kode tidak ter-expose sebagai teks, tersimpan hashed di cache, terikat IP client) + `tokenCaptcha` one-time (berlaku 5 menit) + rate limiting; `POST /api/login` wajib menyertakan `captcha_code` & `tokenCaptcha` ([`CaptchaController.php`](app/Http/Controllers/Api/CaptchaController.php), [`CaptchaService.php`](app/Services/CaptchaService.php), [`config/captcha.php`](config/captcha.php)) |
| **REST API Pendaftaran Pasien** | ✅ **Selesai** | `POST /api/pasien` dengan auto-generate No RM (`RM0001`, `RM0002`, dst.) ([`PasienController.php`](app/Http/Controllers/Api/PasienController.php)) |
| **REST API Buat Kunjungan** | ✅ **Selesai** | `POST /api/kunjungan` via input No Rekam Medik/Pasien ID & realtime visit count ([`KunjunganController.php`](app/Http/Controllers/Api/KunjunganController.php)) |
| **Spesifikasi & Aset Figma** | ✅ **Selesai** | Seluruh nama field, kolom tabel dashboard, modal Tambah Pasien, dan modal Buat Kunjungan 100% identik dengan Figma |
| **Database Migration (MySQL)** | ✅ **Selesai** | Tabel `users`, `pasiens`, `kunjungans`, `personal_access_tokens` ([`database/migrations/`](database/migrations)) |
| **User & Patient Seeder** | ✅ **Selesai** | User `admin` / `password` dan 2 data pasien referensi Figma (`RM0001` - 8 kunjungan, `RM0002` - 0 kunjungan) |
| **Postman Collection Terlampir** | ✅ **Selesai** | File [`Medisin_API.postman_collection.json`](./Medisin_API.postman_collection.json) + lampiran raw JSON lengkap di README |
| **Unit & Feature Testing** | ✅ **Selesai** | **50 tests, 169 assertions (100% Passed)** menggunakan PHPUnit, termasuk negative test untuk setiap endpoint |

### ✅ Pemenuhan Catatan Spesifikasi (Catatan Tugas)
Seluruh poin **Catatan** pada spesifikasi telah terpenuhi:

| Catatan | Pemenuhan Implementasi |
| :--- | :--- |
| *Jumlah Kunjungan didapat dari hasil berapa kali dibuat kunjungan* | Kolom `JML KUNJUNGAN` dihitung dinamis menggunakan `withCount('kunjungans as jml_kunjungan')` — nilai bertambah otomatis setiap `POST /api/kunjungan` berhasil dibuat ([`PasienController.php:19`](app/Http/Controllers/Api/PasienController.php)) |
| *No Rekam Medik dibuat secara Generate Otomatis dengan Format (RM0001 / Urut)* | Method `Pasien::generateNextNoRekamMedik()` menghasilkan nomor sequential `RM0001`, `RM0002`, `RM0003`, dst. melalui event model `creating` ([`Pasien.php:79`](app/Models/Pasien.php)) |
| *Pembuatan Kunjungan hanya menginput No Rekam Medik dan muncul data pasien yang telah didaftarkan* | Endpoint `GET /api/pasien/search/{no_rekam_medik}` mengembalikan data pasien (Nama, NIK, Alamat, Jml Kunjungan) untuk modal Buat Kunjungan, dan `POST /api/kunjungan` cukup menerima `no_rekam_medik` saja ([`KunjunganController.php`](app/Http/Controllers/Api/KunjunganController.php)) |
| *Bonus: validasi sebelum input untuk Negative Test* | Validasi ketat pada `StorePasienRequest`, `StoreKunjunganRequest`, & `LoginRequest` (NIK duplikat/format, No RM duplikat/format, pasien tidak ditemukan, captcha salah/reused/expired, field kosong, dll.) yang diujikan melalui **23 skenario negative test** |

---

## 🛡️ Implementasi Security Test

Sistem telah dilengkapi dengan 3 mekanisme keamanan tingkat lanjut sesuai spesifikasi Security Test:

### 1. Enkripsi Payload pada Pengiriman API (Frontend ➔ Backend)
Saat melakukan login, nilai `user_name` / `username`, `password`, dan `captcha_code` dapat dikirimkan dalam bentuk string terenkripsi:
- **Algoritma**: AES-256-CBC (Base64) dengan prepended 16-byte IV atau CryptoJS format (`Salted__`).
- **Contoh Payload Terenkripsi** (sesuai spesifikasi Security Test):
  ```json
  {
    "captcha_code": "FJByiuB4EywR8snCSf7Xr8hK/7/ouveL/XtFPkyJnbjzstJnQrSftnc9N/2oz0wx3nG2ryo7vox+VCbrUFOlFNFFUqW9Xx1jBYDaCkQ3Vd3SdfvgDDEEtjes4AdqJA...",
    "password": "QeftePJYnMkfkyt8JTKFCTJU/+UoAP9B2pR1h28TaB8Y34ApaaRdgigDNh8UYQnUJtMp98CAxaQdauZSONwaYGd1Cn3tNXR8HHzzoZ5gTQJ1d/ptuEAwVoi/846I40eCnDB...",
    "tokenCaptcha": "b0229394-45af-4502-904a-8694524afcbd",
    "user_name": "gIw6itZt0kjXhz9UZJrZs67X7jtiyzZKNtKoNueWK775EknB7mY+RPYZEbNyBnotaIOHW7JU1vc1T4rU53flwptrjj31SGWOEOUzlnbQrqrrWs4HX1CQKE2DbkWvwcFeFK..."
  }
  ```
  > `tokenCaptcha` dikirim plain (hanya identifier one-time), sedangkan `captcha_code`, `user_name`, dan `password` terenkripsi AES-256-CBC.
- **Backend Service**: [`SecurityService.php`](app/Services/SecurityService.php) secara otomatis mengenali dan mendekripsi payload terenkripsi (termasuk `captcha_code`) sebelum proses validasi FormRequest dan autentikasi dijalankan.
- **Enkripsi Client-Side**: Frontend (Vue) mengenkripsi payload langsung di browser menggunakan **Web Crypto API** (`AES-256-CBC`, kunci = `SHA-256(passphrase)`, IV acak 16-byte) sebelum request dikirim — lihat [`frontend-medisin/src/services/security.js`](../frontend-medisin/src/services/security.js). Shared key dikonfigurasi via env `SECURITY_PAYLOAD_KEY` (backend) dan `VITE_SECURITY_PAYLOAD_KEY` (frontend).
- **Dukungan Fleksibel**: API juga tetap mendukung plaintext payload secara transparan untuk pengujian langsung.
- **Endpoint Utilitas**: Disediakan endpoint `POST /api/security/encrypt` untuk mempermudah pembuatan string terenkripsi dari nilai input apapun saat pengujian.

### 2. Enkripsi Data pada Database (Encryption-at-Rest)
Data sensitif pasien dan pengguna dienkripsi langsung pada level database menggunakan Laravel Cryptography:
- **Kolom `nik` pada tabel `pasiens`**: Disimpan dalam bentuk ciphertext AES (format Base64 JSON `{"iv":..., "value":..., "mac":...}`).
- **Kolom `email` pada tabel `users`**: Disimpan dalam bentuk ciphertext AES.
- **Bukti Langsung di Database MySQL**:
  ```sql
  SELECT id, no_rekam_medik, nama_pasien, LEFT(nik, 40) as nik_encrypted FROM pasiens;
  ```
  *Hasil:*
  | id | no_rekam_medik | nama_pasien | nik_encrypted |
  | :-: | :--- | :--- | :--- |
  | 1 | RM0001 | NURFAZA M. | `eyJpdiI6IitncTJBYnVkcHd5QUdoSjNXVUxQU1E9...` |
  | 2 | RM0002 | NURFAZA D. | `eyJpdiI6Ik0wOTl2bWUzeUVGRmxSbmY2OEdEZ1E9...` |

- **Blind Indexing (SHA-256)**: Kolom `nik_hash` dan `email_hash` digunakan sebagai indeks hashing satu arah untuk memungkinkan pengecekan keunikan (*unique validation*) dan pencarian data tanpa perlu mendekripsi seluruh baris database (mematuhi standar arsitektur keamanan data medis/finansial).

### 3. Captcha Login (Gambar + Token One-Time)
Setiap percobaan login kini wajib disertai captcha untuk mencegah serangan *brute-force*:
- **Generate Captcha**: `GET /api/captcha` menghasilkan `captcha_image` (gambar PNG base64 dengan noise & karakter terdistorsi) dan `tokenCaptcha` (UUID) yang berlaku selama **5 menit** ([`CaptchaService.php`](app/Services/CaptchaService.php)).
- **Kode TIDAK Ter-expose**: `captcha_code` tidak pernah dikirim sebagai teks pada response normal — bot tidak bisa membacanya secara otomatis. Kode hanya dapat dibaca dari gambar.
- **Kode Disimpan Ter-hash**: Backend tidak pernah menyimpan plaintext captcha di cache — hanya SHA-256 hash-nya (format `codeHash|ip`), membandingkan dengan `hash_equals` (tahan *timing attack*).
- **IP Binding**: Token captcha hanya valid untuk verifikasi dari **IP client yang sama** dengan saat token digenerate — token yang dicuri/divalidasi dari IP lain otomatis ditolak (dapat dimatikan via `CAPTCHA_BIND_IP=false`).
- **Mode Debug (Double Gate)**: `?debug=1` hanya berfungsi jika **kedua** syarat terpenuhi: environment bukan production **dan** flag eksplisit `CAPTCHA_DEBUG_ENABLED=true` (default `false` di `.env.example`). Kesalahan set `APP_ENV` di production saja tidak cukup untuk membocorkan kode.
- **TTL via Config**: Masa berlaku captcha dikonfigurasi via env `CAPTCHA_TTL_SECONDS` (default 300 detik).
- **Verifikasi One-Time**: `POST /api/login` wajib mengirim `captcha_code` & `tokenCaptcha`. Token hanya bisa dipakai **satu kali** — otomatis dihapus dari cache setelah verifikasi pertama (berlaku atau gagal).
- **Rate Limiting**: Endpoint `/api/login` dibatasi **10 request/menit** dan `/api/captcha` **30 request/menit** via middleware `throttle`.
- **Hardening Tambahan**:
  - Token captcha disimpan **ter-hash** (SHA-256) di cache — bocornya cache tidak langsung membocorkan kode.
  - Token **terikat ke IP client** — verifikasi dari IP berbeda ditolak.
  - Mode debug `?debug=1` memerlukan **double gate**: non-production + flag `CAPTCHA_DEBUG_ENABLED=true`.
  - Kunci token di cache = `SHA-256(token)` — UUID token asli tidak pernah tersimpan di server.
- **Contoh Alur**:
  ```bash
  # 1. Ambil captcha (gambar PNG base64 + token)
  curl http://127.0.0.1:8000/api/captcha
  # {
  #   "success": true,
  #   "data": {
  #     "captcha_image": "data:image/png;base64,iVBORw0KGgo...",
  #     "tokenCaptcha": "9c1f0a2e-....",
  #     "expires_in": 300
  #   }
  # }
  # -> Buka isi captcha_image di browser untuk melihat kode captcha

  # 1b. (Non-production) Ambil captcha beserta kode plaintext untuk testing
  curl "http://127.0.0.1:8000/api/captcha?debug=1"

  # 2. Login dengan captcha
  curl -X POST http://127.0.0.1:8000/api/login \
    -H "Content-Type: application/json" \
    -d '{"username":"admin","password":"password","captcha_code":"H7K2M","tokenCaptcha":"9c1f0a2e-...."}'
  ```
- **Negative Test**: Login tanpa captcha, captcha salah, token tidak valid/expired, dan token yang sudah dipakai ulang akan ditolak dengan status `422`.

---

## 🚀 Quick Start (Panduan Instalasi Cepat)

Ikuti langkah-langkah mudah berikut untuk menjalankan backend di lokal Anda:

### 1. Clone Repositori & Install Dependencies
```bash
git clone https://github.com/username/backend-medisin.git
cd backend-medisin
composer install
```

### 2. Setup Environment
Salin file environment:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database MySQL di file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medisin
DB_USERNAME=root
DB_PASSWORD=
```
*(Pastikan database `medisin` telah dibuat di MySQL/phpMyAdmin/Laragon Anda)*

Untuk enkripsi payload login, tambahkan shared key (harus sama dengan frontend):
```env
SECURITY_PAYLOAD_KEY=MedisinSecretKey2026PayloadAES!

# Captcha (default aman untuk production)
CAPTCHA_DEBUG_ENABLED=false
CAPTCHA_BIND_IP=true
CAPTCHA_TTL_SECONDS=300
```
*(Nilai ini sudah tersedia di `.env.example` dan otomatis digunakan oleh `SecurityService` & `CaptchaService`. Di sisi frontend, samakan `SECURITY_PAYLOAD_KEY` pada env `VITE_SECURITY_PAYLOAD_KEY`. Untuk testing lokal di Postman, `CAPTCHA_DEBUG_ENABLED` boleh diubah `true`.)*

### 3. Eksekusi Migrasi & Seeder Data Awal
```bash
php artisan migrate:fresh --seed
```

### 4. Jalankan Server API
```bash
php artisan serve
```
Backend REST API akan berjalan dan siap diuji pada: **`http://127.0.0.1:8000/api`**

---

## 🔐 Akun Login & Data Seeder Awal

Database Seeder otomatis mengisi kredensial login dan data pasien awal yang **persis sama** dengan screenshot referensi Figma:

### Kredensial Login (User Seeder)
- **URL Login**: `POST http://127.0.0.1:8000/api/login`
- **Username**: `admin`
- **Password**: `password`
- *(Email di database terenkripsi, didekripsi sebagai: `admin@medisin.com`)*

### Data Pasien Awal (Pasien Seeder)
| NO | NO REKAM MEDIK | NAMA PASIEN | NIK (Didekripsi di API) | ALAMAT | JML KUNJUNGAN |
| :-: | :--- | :--- | :--- | :--- | :-: |
| 1 | `RM0001` | **NURFAZA M.** | `321412412312412` | Jl Wisata 2 No 18 | **8** |
| 2 | `RM0002` | **NURFAZA D.** | `321412412355553` | Jl Wisata 2 No 18 | **0** |

---

## 📮 Postman Collection & Pengujian API

File Postman Collection v2.1 telah disertakan di root repository:
👉 **[Medisin_API.postman_collection.json](./Medisin_API.postman_collection.json)**
*(Juga tersedia di folder [`postman/Medisin_API.postman_collection.json`](./postman/Medisin_API.postman_collection.json))*

### ⚡ Fitur Otomatisasi Postman (Zero-Config)
1. Buka Postman, klik tombol **Import**, lalu pilih file `Medisin_API.postman_collection.json`.
2. Jalankan request **`1. Authentication & Security > Login (Encrypted Payload - Security Test)`**:
   - Pre-request script otomatis mengambil captcha baru (`tokenCaptcha` + `captcha_code`) dari endpoint `GET /captcha?debug=1`.
   - Pre-request script otomatis mengenkripsi `captcha_code`, `user_name`, & `password` secara dinamis menggunakan CryptoJS AES-256-CBC (kunci = `SHA-256(passphrase)`, sama dengan backend).
   - Test script otomatis menyimpan token Bearer ke variable `{{token}}`.
3. Seluruh request pada folder **`Pasien`** dan **`Kunjungan`** otomatis terotentikasi tanpa perlu copy-paste token manual!

---

## 📖 Ringkasan Endpoint REST API

Base URL: `http://127.0.0.1:8000/api`

### 1. Autentikasi & Security
| Method | Endpoint | Deskripsi | Auth |
| :--- | :--- | :--- | :---: |
| `GET` | `/captcha` | Generate gambar captcha (PNG base64) + token one-time (berlaku 5 menit, terikat IP client; `?debug=1` butuh `CAPTCHA_DEBUG_ENABLED=true` di non-production). Rate limit 30 req/menit | Publik |
| `POST` | `/login` | Login user (wajib sertakan `captcha_code` & `tokenCaptcha`; mendukung payload terenkripsi maupun plain). Rate limit 10 req/menit | Publik |
| `POST` | `/security/encrypt` | Utilitas enkripsi nilai input payload untuk pengujian | Publik |
| `GET` | `/me` | Melihat profil user yang sedang aktif | Bearer |
| `POST` | `/logout` | Logout dan mencabut Bearer token aktif | Bearer |

### 2. Pasien (Patient Management)
| Method | Endpoint | Deskripsi | Auth |
| :--- | :--- | :--- | :---: |
| `GET` | `/pasien` | Menampilkan seluruh data pasien + jumlah kunjungan (`jml_kunjungan`) | Bearer |
| `POST` | `/pasien` | Pendaftaran pasien baru (Auto-generate No Rekam Medik `RM0003`, NIK terenkripsi di DB) | Bearer |
| `GET` | `/pasien/search/{no_rekam_medik}` | Mencari data pasien by No RM untuk modal **Buat Kunjungan** Figma | Bearer |
| `GET` | `/pasien/{id}` | Mengambil detail pasien dan seluruh histori kunjungannya | Bearer |

### 3. Kunjungan (Patient Visits)
| Method | Endpoint | Deskripsi | Auth |
| :--- | :--- | :--- | :---: |
| `POST` | `/kunjungan` | Buat kunjungan baru via `no_rekam_medik` (atau `pasien_id`) | Bearer |
| `GET` | `/kunjungan` | Menampilkan seluruh daftar riwayat kunjungan | Bearer |
| `GET` | `/kunjungan?no_rekam_medik=RM0001` | Filter riwayat kunjungan pasien tertentu | Bearer |
| `GET` | `/kunjungan/{id}` | Detail riwayat kunjungan | Bearer |

---

## 🧪 Pengujian Otomatis (PHPUnit Test Suite)

Proyek ini dilengkapi dengan 50 skenario automated test yang mencakup pengujian otentikasi, captcha login, payload encryption, database encryption-at-rest, validasi data, relasi Eloquent, dan error handling.

Jalankan test suite menggunakan perintah:
```bash
php artisan test
```

**Hasil Pengujian:**
```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

    PASS  Tests\Feature\AuthApiTest
  ✓ user can login with valid username and password
  ✓ user can login with encrypted payload
  ✓ user can login with encrypted captcha code
  ✓ user email is encrypted at rest in database
  ✓ user can login with valid email and password
  ✓ login fails with invalid credentials
  ✓ login validation fails when fields are missing
  ✓ captcha endpoint returns image and token without exposing code
  ✓ captcha debug param is ignored by default
  ✓ captcha debug mode exposes code when explicitly enabled
  ✓ captcha token is bound to requesting client ip
  ✓ captcha code is stored hashed not as plaintext
  ✓ login fails when captcha is missing
  ✓ login fails with wrong captcha code
  ✓ login fails when captcha token is invalid or expired
  ✓ login fails when captcha token is reused
  ✓ authenticated user can get profile
  ✓ authenticated user can logout
  ✓ security encrypt helper endpoint

   PASS  Tests\Feature\PasienApiTest
  ✓ unauthenticated request is unauthorized
  ✓ can list patients with visit counts
  ✓ can register new patient with auto generated no rekam medik
  ✓ auto generates sequential no rekam medik
  ✓ can register patient with custom no rekam medik
  ✓ validation fails when required fields are missing
  ✓ validation fails when nik is duplicate
  ✓ can find patient by no rekam medik
  ✓ finding non existent patient returns 404
  ✓ can get patient detail
  ✓ validation fails when nik contains non numeric characters
  ✓ validation fails when nik is too short
  ✓ validation fails when nik is too long
  ✓ validation fails when nama pasien is too short
  ✓ validation fails when alamat is too short
  ✓ validation fails when no rekam medik has invalid format
  ✓ validation fails when no rekam medik is duplicate
  ✓ get non existent patient detail returns 404

   PASS  Tests\Feature\KunjunganApiTest
  ✓ unauthenticated user cannot access kunjungan
  ✓ can create kunjungan using no rekam medik
  ✓ can create kunjungan using pasien id
  ✓ create kunjungan fails with invalid no rekam medik
  ✓ create kunjungan increments jml kunjungan in pasien list
  ✓ can list kunjungans
  ✓ can filter kunjungans by pasien id
  ✓ can get kunjungan detail
  ✓ create kunjungan fails with empty body
  ✓ create kunjungan fails with non existent pasien id
  ✓ get non existent kunjungan returns 404

  Tests:    50 passed (169 assertions)
  Duration: 2.22s
```

---

## 🎬 Demo &amp; Presentasi

- **[`demo-animation.html`](demo-animation.html)** — animasi demo satu file (tanpa dependency). Buka di Chrome/Edge, jalan otomatis 11 scene (±2,5 menit): login + payload terenkripsi, kode route/model/service/request, dashboard (tambah pasien & kunjungan), bukti ciphertext di database, hingga test suite. Kontrol: `F` fullscreen, `Space` pause, `←/→` ganti scene, `R` ulang.
- **[`DEMO_SCRIPT.md`](DEMO_SCRIPT.md)** — naskah walkthrough kode lengkap per file (±15 menit) dengan narasi dan poin spesifikasi.

---

## 🏛️ Arsitektur & Best Practices yang Diterapkan

1. **Security & Cryptography**: Enkripsi payload transport client-side (AES-256-CBC via Web Crypto di frontend & CryptoJS di Postman) dan enkripsi database (*encryption-at-rest*) untuk perlindungan data pribadi pasien (NIK) dan akun (Email).
2. **Captcha & Rate Limiting**: Login dilindungi captcha gambar one-time (5 menit, token sekali pakai) dan middleware `throttle` (10 req/menit untuk login, 30 req/menit untuk captcha).
3. **Eloquent API Resources**: Mengisolasi representasi data output dengan model internal untuk menjaga konsistensi format respons JSON.
4. **Form Request Validation**: Logika validasi dan dekripsi dipisahkan dari controller (`LoginRequest`, `StorePasienRequest`, `StoreKunjunganRequest`).
5. **Pencegahan N+1 Query**: Menggunakan `withCount('kunjungans as jml_kunjungan')` dan eager-loading `with('pasien')` untuk memastikan performa database optimal.
6. **Auto-Generate Nomor Rekam Medik Portabel**: Penomoran sequential otomatis yang kompatibel dengan berbagai driver database.
7. **Code Style & Formatting**: Diformat rapi mematuhi standar PSR-12 menggunakan **Laravel Pint**.

---

## 📦 Lampiran Raw Postman Collection v2.1 JSON

Jika Anda ingin langsung menyalin teks Postman Collection tanpa mendownload file, silakan buka dropdown di bawah ini:

<details>
<summary><b>Klik untuk melihat / menyalin Postman Collection JSON</b></summary>

```json
{
	"info": {
		"_postman_id": "a8f349d1-0f4b-4b15-9c88-e9f02c6114a1",
		"name": "Medisin REST API",
		"description": "Postman Collection untuk REST API Medisin - Sistem Informasi Rekam Medik & Kunjungan Pasien.\n\nDilengkapi dengan fitur Security Test (Enkripsi Payload Username/Password/captcha_code & Enkripsi Database NIK/Email), Captcha Login (gambar + token one-time), otentikasi Bearer Token (Laravel Sanctum), auto-token script pada Login, pendaftaran pasien dengan auto-increment No Rekam Medik, pencarian pasien, dan pembuatan kunjungan.",
		"schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
	},
	"item": [
		{
			"name": "1. Authentication & Security",
			"item": [
				{
					"name": "Get Captcha (Token & Code)",
					"event": [
						{
							"listen": "test",
							"script": {
								"exec": [
									"pm.test(\"Status code is 200\", function () {",
									"    pm.response.to.have.status(200);",
									"});",
									"",
									"var jsonData = pm.response.json();",
									"if (jsonData.data && jsonData.data.captcha_code) {",
									"    pm.collectionVariables.set(\"captcha_code\", jsonData.data.captcha_code);",
									"}",
									"if (jsonData.data && jsonData.data.tokenCaptcha) {",
									"    pm.collectionVariables.set(\"tokenCaptcha\", jsonData.data.tokenCaptcha);",
									"}"
								],
								"type": "text/javascript"
							}
						}
					],
					"request": {
						"auth": {
							"type": "noauth"
						},
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/captcha?debug=1",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"captcha"
							],
							"query": [
								{
									"key": "debug",
									"value": "1",
									"description": "Hanya berfungsi di environment non-production: mengembalikan captcha_code plaintext untuk testing manual API"
								}
							]
						},
						"description": "Membuat captcha baru (gambar PNG + token one-time, berlaku 5 menit, terikat IP client) yang wajib disertakan pada setiap request login. Secara default kode captcha TIDAK dikirim sebagai teks (hanya gambar). Query ?debug=1 mengembalikan captcha_code plaintext, hanya untuk environment non-production dengan flag CAPTCHA_DEBUG_ENABLED=true. Script Test otomatis menyimpan captcha_code dan tokenCaptcha ke collection variable."
					},
					"response": []
				},
				{
					"name": "Login (Encrypted Payload - Security Test)",
					"event": [
						{
							"listen": "prerequest",
							"script": {
								"exec": [
									"// Fetch a fresh captcha (image + one-time token) before login",
									"pm.sendRequest(pm.collectionVariables.get(\"base_url\") + \"/captcha?debug=1\", function (err, res) {",
									"    if (!err && res) {",
									"        var data = res.json().data;",
									"        pm.collectionVariables.set(\"tokenCaptcha\", data.tokenCaptcha);",
									"",
									"        // Encrypt the captcha code (AES-256-CBC, IV + Ciphertext, Base64)",
									"        var captchaKey = CryptoJS.SHA256(\"MedisinSecretKey2026PayloadAES!\");",
									"        var captchaIv = CryptoJS.lib.WordArray.random(16);",
									"        var encCode = CryptoJS.AES.encrypt(data.captcha_code, captchaKey, { iv: captchaIv });",
									"        var fullCode = captchaIv.clone().concat(encCode.ciphertext);",
									"        pm.collectionVariables.set(\"captcha_code\", CryptoJS.enc.Base64.stringify(fullCode));",
									"    }",
									"});",
									"",
									"// AES-256-CBC Payload Encryption using CryptoJS",
									"var key = CryptoJS.SHA256(\"MedisinSecretKey2026PayloadAES!\");",
									"var iv = CryptoJS.lib.WordArray.random(16);",
									"",
									"var encUser = CryptoJS.AES.encrypt(\"admin\", key, { iv: iv });",
									"var encPass = CryptoJS.AES.encrypt(\"password\", key, { iv: iv });",
									"",
									"// Combine IV (16 bytes) + Ciphertext into single Base64 string",
									"var fullUser = iv.clone().concat(encUser.ciphertext);",
									"var fullPass = iv.clone().concat(encPass.ciphertext);",
									"",
									"pm.environment.set(\"encrypted_username\", CryptoJS.enc.Base64.stringify(fullUser));",
									"pm.environment.set(\"encrypted_password\", CryptoJS.enc.Base64.stringify(fullPass));"
								],
								"type": "text/javascript"
							}
						},
						{
							"listen": "test",
							"script": {
								"exec": [
									"pm.test(\"Status code is 200\", function () {",
									"    pm.response.to.have.status(200);",
									"});",
									"",
									"var jsonData = pm.response.json();",
									"if (jsonData.data && jsonData.data.token) {",
									"    pm.collectionVariables.set(\"token\", jsonData.data.token);",
									"    console.log(\"Token saved successfully from encrypted login\");",
									"}"
								],
								"type": "text/javascript"
							}
						}
					],
					"request": {
						"auth": {
							"type": "noauth"
						},
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"user_name\": \"{{encrypted_username}}\",\n    \"password\": \"{{encrypted_password}}\",\n    \"captcha_code\": \"{{captcha_code}}\",\n    \"tokenCaptcha\": \"{{tokenCaptcha}}\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/login",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"login"
							]
						},
						"description": "Security Test: Mengirimkan username dan password yang terenkripsi AES-256-CBC pada payload request. Pre-request script secara otomatis mengambil captcha baru dan mengenkripsi captcha_code, username & password sebelum dikirim."
					},
					"response": []
				},
				{
					"name": "Login (Plain Payload)",
					"event": [
						{
							"listen": "prerequest",
							"script": {
								"exec": [
									"// Fetch a fresh captcha (image + one-time token) before login",
									"pm.sendRequest(pm.collectionVariables.get(\"base_url\") + \"/captcha?debug=1\", function (err, res) {",
									"    if (!err && res) {",
									"        var data = res.json().data;",
									"        pm.collectionVariables.set(\"tokenCaptcha\", data.tokenCaptcha);",
									"",
									"        // Encrypt the captcha code (AES-256-CBC, IV + Ciphertext, Base64)",
									"        var captchaKey = CryptoJS.SHA256(\"MedisinSecretKey2026PayloadAES!\");",
									"        var captchaIv = CryptoJS.lib.WordArray.random(16);",
									"        var encCode = CryptoJS.AES.encrypt(data.captcha_code, captchaKey, { iv: captchaIv });",
									"        var fullCode = captchaIv.clone().concat(encCode.ciphertext);",
									"        pm.collectionVariables.set(\"captcha_code\", CryptoJS.enc.Base64.stringify(fullCode));",
									"    }",
									"});"
								],
								"type": "text/javascript"
							}
						},
						{
							"listen": "test",
							"script": {
								"exec": [
									"pm.test(\"Status code is 200\", function () {",
									"    pm.response.to.have.status(200);",
									"});",
									"",
									"var jsonData = pm.response.json();",
									"if (jsonData.data && jsonData.data.token) {",
									"    pm.collectionVariables.set(\"token\", jsonData.data.token);",
									"    console.log(\"Token saved successfully: \" + jsonData.data.token);",
									"}"
								],
								"type": "text/javascript"
							}
						}
					],
					"request": {
						"auth": {
							"type": "noauth"
						},
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"username\": \"admin\",\n    \"password\": \"password\",\n    \"captcha_code\": \"{{captcha_code}}\",\n    \"tokenCaptcha\": \"{{tokenCaptcha}}\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/login",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"login"
							]
						},
						"description": "Login menggunakan username 'admin' dan password 'password' biasa. Pre-request script otomatis mengambil captcha baru dan mengenkripsi captcha_code (username/password tetap plain untuk skenario ini). Script Test otomatis menyimpan token ke collection variable {{token}}."
					},
					"response": []
				},
				{
					"name": "Security - Encrypt Payload Helper",
					"request": {
						"auth": {
							"type": "noauth"
						},
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"username\": \"admin\",\n    \"password\": \"password\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/security/encrypt",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"security",
								"encrypt"
							]
						},
						"description": "Endpoint utilitas untuk menguji atau menghasilkan string terenkripsi AES-256-CBC dari payload input."
					},
					"response": []
				},
				{
					"name": "User Profile (Me)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/me",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"me"
							]
						},
						"description": "Mengambil informasi profil user yang sedang login."
					},
					"response": []
				},
				{
					"name": "Logout",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/logout",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"logout"
							]
						},
						"description": "Mencabut token autentikasi yang sedang aktif."
					},
					"response": []
				}
			]
		},
		{
			"name": "2. Pasien",
			"item": [
				{
					"name": "Daftar Pasien (List Pasien)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/pasien",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien"
							]
						},
						"description": "Menampilkan seluruh daftar pasien beserta jumlah kunjungannya (JML KUNJUNGAN) sesuai tabel dashboard Figma."
					},
					"response": []
				},
				{
					"name": "Cari Pasien (Filter/Search)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/pasien?search=NURFAZA",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien"
							],
							"query": [
								{
									"key": "search",
									"value": "NURFAZA",
									"description": "Cari berdasarkan nama, no rekam medik, atau NIK"
								}
							]
						},
						"description": "Pencarian pasien berdasarkan nama, no rekam medik, atau NIK."
					},
					"response": []
				},
				{
					"name": "Cari Pasien by No Rekam Medik (Modal Buat Kunjungan)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/pasien/search/RM0001",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien",
								"search",
								"RM0001"
							]
						},
						"description": "Endpoint khusus untuk modal 'BUAT KUNJUNGAN' pada Figma. Mengambil detail pasien (Nama, NIK, Alamat) saat No Rekam Medik diinputkan."
					},
					"response": []
				},
				{
					"name": "Tambah Pasien (Pendaftaran Pasien - Auto No RM)",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"nama_pasien\": \"Budi Santoso\",\n    \"nik\": \"3214124987654321\",\n    \"alamat\": \"Jl. Merdeka No. 12, Bandung\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/pasien",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien"
							]
						},
						"description": "Mendaftarkan pasien baru. NIK otomatis tersimpan terenkripsi di database. No Rekam Medik akan digenerate otomatis dengan format sequential RM0001, RM0002, RM0003, dst."
					},
					"response": []
				},
				{
					"name": "Tambah Pasien (Custom No Rekam Medik)",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"no_rekam_medik\": \"RM0099\",\n    \"nama_pasien\": \"Siti Rahmawati\",\n    \"nik\": \"3214124987659999\",\n    \"alamat\": \"Jl. Anggrek No. 5, Cimahi\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/pasien",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien"
							]
						},
						"description": "Mendaftarkan pasien dengan Nomor Rekam Medik manual/spesifik jika diperlukan."
					},
					"response": []
				},
				{
					"name": "Detail Pasien (By ID)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/pasien/1",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"pasien",
								"1"
							]
						},
						"description": "Mengambil detail pasien beserta seluruh riwayat kunjungannya."
					},
					"response": []
				}
			]
		},
		{
			"name": "3. Kunjungan",
			"item": [
				{
					"name": "Buat Kunjungan (via No Rekam Medik)",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"no_rekam_medik\": \"RM0002\",\n    \"keluhan\": \"Pemeriksaan tensi dan gula darah\",\n    \"catatan\": \"Kondisi umum baik\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/kunjungan",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"kunjungan"
							]
						},
						"description": "Membuat kunjungan baru menggunakan input No Rekam Medik (sesuai form modal 'BUAT KUNJUNGAN' Figma)."
					},
					"response": []
				},
				{
					"name": "Buat Kunjungan (via Pasien ID)",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"pasien_id\": 1,\n    \"keluhan\": \"Konsultasi keluhan batuk berdahak\",\n    \"catatan\": \"Diberi resep obat batuk\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/kunjungan",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"kunjungan"
							]
						},
						"description": "Membuat kunjungan baru dengan mengirimkan ID pasien secara langsung."
					},
					"response": []
				},
				{
					"name": "Daftar Semua Kunjungan",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/kunjungan",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"kunjungan"
							]
						},
						"description": "Menampilkan seluruh data riwayat kunjungan pasien yang tercatat."
					},
					"response": []
				},
				{
					"name": "Filter Kunjungan Pasien (via Query Param)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/kunjungan?no_rekam_medik=RM0001",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"kunjungan"
							],
							"query": [
								{
									"key": "no_rekam_medik",
									"value": "RM0001",
									"description": "Filter kunjungan berdasarkan no rekam medik"
								}
							]
						},
						"description": "Menampilkan daftar kunjungan khusus untuk pasien dengan No Rekam Medik tertentu."
					},
					"response": []
				},
				{
					"name": "Detail Kunjungan (By ID)",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/kunjungan/1",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"kunjungan",
								"1"
							]
						},
						"description": "Menampilkan detail satu data kunjungan beserta relasi data pasiennya."
					},
					"response": []
				}
			]
		}
	],
	"auth": {
		"type": "bearer",
		"bearer": [
			{
				"key": "token",
				"value": "{{token}}",
				"type": "string"
			}
		]
	},
	"event": [
		{
			"listen": "prerequest",
			"script": {
				"type": "text/javascript",
				"exec": [
					""
				]
			}
		},
		{
			"listen": "test",
			"script": {
				"type": "text/javascript",
				"exec": [
					""
				]
			}
		}
	],
	"variable": [
		{
			"key": "base_url",
			"value": "http://127.0.0.1:8000/api",
			"type": "string"
		},
		{
			"key": "token",
			"value": "",
			"type": "string"
		},
		{
			"key": "captcha_code",
			"value": "",
			"type": "string"
		},
		{
			"key": "tokenCaptcha",
			"value": "",
			"type": "string"
		},
		{
			"key": "encrypted_username",
			"value": "",
			"type": "string"
		},
		{
			"key": "encrypted_password",
			"value": "",
			"type": "string"
		}
	]
}
```

</details>
