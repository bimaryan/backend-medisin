# 🎬 Script Video Demo — Technical Test Fullstack Developer PT SINTASI
## Penjelasan SEMUA Kode: Migrasi → Route → Controller → Model → Service → Request

> Format: walkthrough seluruh kode backend, dikelompokkan per kategori sesuai urutan eksekusi aplikasi.
> Setiap step dilengkapi: **[FILE]** → **[KODE UTAMA]** → **[TUNJUKKAN]** → **[NARASI]** (teks yang dibaca) → **[POIN]** (poin spesifikasi).
> Total ±15 menit. Rekam per step.

---

## 🎞️ Opsi Animasi Siap Rekam (Otomatis dengan Voiceover)

Tersedia **[`demo-animation.html`](demo-animation.html)** — animasi demo satu file (tanpa build/dependency) yang memutar sendiri alur web + narasi suara otomatis + penjelasan kode lengkap:

1. Buka `demo-animation.html` di Chrome/Edge (double click).
2. Animasi berjalan otomatis (**13 scene lengkap, ±3 menit dengan narasi suara bahasa Indonesia**):
   - Scene 1: Judul & Tech Stack
   - Scene 2: Demo Web Login & DevTools Payload Terenkripsi AES-256-CBC
   - Scene 3: Skema Migrasi Database (`pasiens` NIK encrypted & blind index, `kunjungans` cascade)
   - Scene 4: Peta Route & Rate Limiting (`routes/api.php`)
   - Scene 5: Model Pasien (Auto RM0001, RM0002 urut & Mutator NIK)
   - Scene 6: SecurityService (Enkripsi AES-256-CBC & IV Acak)
   - Scene 7: CaptchaService (4 Lapis Pertahanan Defense in Depth)
   - Scene 8: FormRequest (`StorePasienRequest` validasi blind index & `StoreKunjunganRequest` auto-resolve)
   - Scene 9: Controller (`PasienController` withCount anti N+1 & search modal Buat Kunjungan)
   - Scene 10: Demo Web Dashboard (Tambah Pasien RM0003 & Buat Kunjungan via No RM)
   - Scene 11: Bukti Langsung Enkripsi Database MySQL (Raw Ciphertext `eyJpdi...`)
   - Scene 12: Automated Test Suite (50 tests, 169 assertions lolos 100%)
   - Scene 13: Ringkasan & Checklist Lengkap Catatan #1–#4
3. Kontrol: `F` fullscreen, `Space` pause/play, `←/→` ganti scene, `R` ulang, **`V` toggle suara narasi**.
4. Rekam dengan **OBS** (1080p) untuk mendapatkan video presentasi lengkap audio dan animasi visual profesional.

---

## ⏱️ Struktur Script (urutan rekomendasi)

| # | Kategori | File Utama | Durasi | Poin Spesifikasi yang Dijawab |
| :-: | :--- | :--- | :---: | :--- |
| 1 | **MIGRASI** (4 file) | pasiens, kunjungans, users, tokens | 2:00 | Catatan #2 (No RM unik), Catatan #1 (relasi), Security Test (encryption-at-rest) |
| 2 | **ROUTE** (1 file) | `routes/api.php` | 1:30 | Rate limiting brute force (10/min, 30/min), Sanctum auth, route order |
| 3 | **MODEL** (3 file) | Pasien, Kunjungan, User | 3:00 | Catatan #2 (Auto RM0001 urut), Mutator NIK encrypt & SHA-256 blind index |
| 4 | **SERVICE** (2 file) | SecurityService, CaptchaService | 3:00 | Security Test #1 (Payload AES-256-CBC), Captcha 4 lapis defense in depth |
| 5 | **REQUEST** (3 file) | LoginRequest, StorePasien, StoreKunjungan | 2:30 | Dekripsi payload, Catatan #3 (Auto resolve No RM ke ID), Blind index check |
| 6 | **CONTROLLER** (5 file) | Auth, Captcha, Security, Pasien, Kunjungan | 2:30 | Catatan #1 (withCount anti N+1), Catatan #3 (findByRekamMedik modal), resource |
| 7 | **DEMO LIVE & TEST** | Browser DevTools ⭐, MySQL, PHPUnit | 3:00 | Bukti payload terenkripsi, bukti DB ciphertext, 50 automated tests passed |

---

# BAGIAN 1 — MIGRASI (Struktur Database)

### STEP 1 — Migrasi `pasiens` (±45 dtk)

**[FILE]** `database/migrations/2026_11_11_025100_create_pasiens_table.php`

**[KODE UTAMA]**
```php
Schema::create('pasiens', function (Blueprint $table) {
    $table->id();
    $table->string('no_rekam_medik')->unique(); // Catatan #2: Unik & Format Urut
    $table->string('nama_pasien');
    $table->text('nik'); // Security Test: Tersimpan Terenkripsi AES (Encryption-at-rest)
    $table->string('nik_hash', 64)->nullable()->unique(); // Blind Index SHA-256 untuk Unique & Search O(1)
    $table->text('alamat');
    $table->timestamps();
});
```

**[TUNJUKKAN]**
- `no_rekam_medik` → `string, unique`
- `nama_pasien`, `alamat` → text
- `nik` → `text` + komentar `// Encrypted at rest`
- `nik_hash` → `string(64), nullable, unique` + komentar blind index

**[NARASI]**
> "Migrasi tabel pasiens. Perhatikan dua kolom kunci: kolom `nik` bertipe text karena menyimpan ciphertext AES — bukan plaintext — dan `no_rekam_medik` unique karena jadi kunci pencarian untuk pembuatan kunjungan. Kolom `nik_hash` sepanjang 64 karakter menyimpan SHA-256 hex satu arah sebagai blind index: unique untuk validasi keunikan dan cepat dicari tanpa mendekripsi seluruh baris."

**[POIN]** Skema mendukung Catatan #2 (No RM unik) + encryption-at-rest.

---

### STEP 2 — Migrasi `kunjungans` (±40 dtk)

**[FILE]** `database/migrations/2026_11_11_025105_create_kunjungans_table.php`

**[KODE UTAMA]**
```php
Schema::create('kunjungans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pasien_id')->constrained('pasiens')->cascadeOnDelete();
    $table->timestamp('tanggal_kunjungan')->useCurrent();
    $table->text('keluhan')->nullable();
    $table->text('catatan')->nullable();
    $table->timestamps();
});
```

**[TUNJUKKAN]**
- `pasien_id` → `foreignId → constrained('pasiens') → cascadeOnDelete()`
- `tanggal_kunjungan` → `timestamp, useCurrent()`
- `keluhan`, `catatan` → `text, nullable`

**[NARASI]**
> "Tabel kunjungans menyimpan setiap kali pasien berobat — foreign key ke pasiens dengan cascade delete, tanggal kunjungan otomatis diisi waktu saat ini. Keluhan dan catatan opsional. Jumlah kunjungan pasien = berapa baris di tabel ini untuk pasien tersebut — bukan field counter, jadi tidak mungkin meleset."

**[POIN]** Catatan #1: *"Jumlah Kunjungan didapat dari hasil berapa kali dibuat kunjungan"*.

---

### STEP 3 — Migrasi `users` + `personal_access_tokens` (±35 dtk)

**[FILE]** `database/migrations/0001_01_01_000000_create_users_table.php`, `..._create_personal_access_tokens_table.php`

**[KODE UTAMA]**
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique();
    $table->text('email'); // Security Test: Email terenkripsi AES di database
    $table->string('email_hash', 64)->unique(); // Blind index SHA-256 untuk login & unique check
    $table->string('password');
    $table->timestamps();
});
```

**[TUNJUKKAN]**
- `users`: kolom `email` text + `email_hash` unique (pola sama dengan NIK)
- `personal_access_tokens`: tabel milik Laravel Sanctum

**[NARASI]**
> "Email di tabel users menerapkan pola yang sama seperti NIK — ciphertext plus blind index hash. Tabel personal_access_tokens adalah penyimpanan token Sanctum yang kita pakai untuk otentikasi Bearer."

**[POIN]** Konsistensi pola enkripsi di seluruh database.

---

# BAGIAN 2 — ROUTE (Peta API)

### STEP 4 — `routes/api.php` (±1:30)

**[FILE]** `routes/api.php`

**[KODE UTAMA]**
```php
// Publik (Anti brute-force dengan Rate Limiting)
Route::get('/captcha', [CaptchaController::class, 'generate'])->middleware('throttle:30,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/security/encrypt', [SecurityController::class, 'encrypt']);

// Terproteksi Bearer token Sanctum
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pasien: Route /search diletakkan SEBELUM /{pasien} wildcard
    Route::get('/pasien', [PasienController::class, 'index']);
    Route::post('/pasien', [PasienController::class, 'store']);
    Route::get('/pasien/search/{no_rekam_medik}', [PasienController::class, 'findByRekamMedik']);
    Route::get('/pasien/{pasien}', [PasienController::class, 'show']);

    // Kunjungan
    Route::post('/kunjungan', [KunjunganController::class, 'store']);
    Route::get('/kunjungan', [KunjunganController::class, 'index']);
});
```

**[TUNJUKKAN]**
- 3 route publik: `GET /captcha` (`throttle:30,1`), `POST /login` (`throttle:10,1`), `POST /security/encrypt`
- Group `auth:sanctum`: `/user`, `/me`, `/logout`
- Pasien: `GET/POST /pasien`, `GET /pasien/search/{no_rekam_medik}`, `GET /pasien/{pasien}`
- Kunjungan: `POST/GET /kunjungan`, `GET /kunjungan/{kunjungan}`

**[NARASI]**
> "Ini peta seluruh API — sembilan endpoint terproteksi Sanctum dan tiga publik. Perhatikan dua hal: rate limiting 30 request per menit di captcha dan 10 di login sebagai pertahanan pertama brute-force. Dan urutan route `search` diletakkan sebelum `pasien/{pasien}` agar tidak bentrok dengan route wildcard."

**[POIN]** Keamanan endpoint + struktur REST.

---

# BAGIAN 3 — MODEL (Domain & Enkripsi Otomatis)

### STEP 5 — `app/Models/Pasien.php` (±1:30)

**[FILE]** `app/Models/Pasien.php`

**[KODE UTAMA]**
```php
protected static function booted(): void
{
    static::creating(function (Pasien $pasien): void {
        if (empty($pasien->no_rekam_medik)) {
            $pasien->no_rekam_medik = static::generateNextNoRekamMedik();
        }
    });
}

public static function generateNextNoRekamMedik(): string
{
    $maxNumber = static::query()->pluck('no_rekam_medik')->map(function ($rm): int {
        return preg_match('/^RM(\d+)$/i', (string) $rm, $matches) ? (int) $matches[1] : 0;
    })->max() ?? 0;

    return sprintf('RM%04d', $maxNumber + 1);
}

public function kunjungans(): HasMany
{
    return $this->hasMany(Kunjungan::class);
}

public function setNikAttribute(?string $value): void
{
    $this->attributes['nik'] = $value !== null && $value !== '' ? Crypt::encryptString($value) : null;
    $this->attributes['nik_hash'] = $value !== null && $value !== '' ? hash('sha256', trim($value)) : null;
}

public function getNikAttribute(?string $value): ?string
{
    return $value !== null ? Crypt::decryptString($value) : null;
}
```

**[TUNJUKKAN]**
- `booted()` → event `creating` → `generateNextNoRekamMedik()` jika No RM kosong
- `generateNextNoRekamMedik()` → ambil semua No RM, parse angka, `sprintf('RM%04d', max+1)`
- `setNikAttribute()` → `Crypt::encryptString` + `hash('sha256')` → isi `nik` & `nik_hash` sekaligus
- `getNikAttribute()` → dekripsi otomatis (accessor)
- Relasi `kunjungans()` → `hasMany`

**[NARASI]**
> "Model Pasien memuat tiga aturan domain penting. Pertama — poin spesifikasi Catatan nomor 2 — No Rekam Medik digenerate otomatis secara urut lewat event creating: sistem mengambil nilai maksimum dari nomor RM yang ada, lalu menambah satu, format RM0001, RM0002, dst. Karena ada di event model, generate ini dijamin jalan apapun jalannya input — controller, seeder, atau factory. Kedua — mutator NIK mengenkripsi dan menghitung blind index dalam satu langkah, sehingga tidak ada jalur kode yang bisa menyimpan NIK plaintext. Ketiga — accessor mendekripsi NIK otomatis, konsumen API menerima plaintext tanpa pernah menyentuh ciphertext."

**[POIN]** Catatan #2 + encryption-at-rest + blind index.

---

### STEP 6 — `app/Models/Kunjungan.php` + `app/Models/User.php` (±1:30)

**[FILE]** `app/Models/Kunjungan.php`, `app/Models/User.php`

**[KODE UTAMA]**
```php
// Kunjungan.php
class Kunjungan extends Model {
    protected $casts = ['tanggal_kunjungan' => 'datetime'];
    public function pasien(): BelongsTo { return $this->belongsTo(Pasien::class); }
}

// User.php
public function setEmailAttribute(?string $value): void {
    $this->attributes['email'] = $value ? Crypt::encryptString(strtolower(trim($value))) : null;
    $this->attributes['email_hash'] = $value ? hash('sha256', strtolower(trim($value))) : null;
}
```

**[TUNJUKKAN]**
- Kunjungan: cast `tanggal_kunjungan` datetime, relasi `pasien()` belongsTo
- User: mutator `setEmailAttribute` (enkripsi + hash lowercase), accessor, `HasApiTokens` (Sanctum), cast `password => hashed`

**[NARASI]**
> "Kunjungan terikat belongsTo ke Pasien, tanggalnya di-cast ke Carbon. Model User menerapkan pola enkripsi yang sama untuk email — mutator mengenkripsi dan menghitung hash email yang di-lowercase agar pencarian konsisten. Trait HasApiTokens memungkinkan pembuatan token Sanctum, dan password otomatis ter-hash oleh Laravel lewat cast."

**[POIN]** Konsistensi arsitektur enkripsi + otentikasi token.

---

# BAGIAN 4 — SERVICE (Business Logic Kriptografi)

### STEP 7 — `app/Services/SecurityService.php` (±1:30)

**[FILE]** `app/Services/SecurityService.php`

**[KODE UTAMA]**
```php
public function encrypt(string $value): string
{
    $iv = openssl_random_pseudo_bytes(16); // IV acak 16 byte tiap request
    $ciphertext = openssl_encrypt($value, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ciphertext); // Output Base64(IV + Ciphertext)
}

public function decrypt(?string $value): ?string
{
    // 1. Coba decrypt via Laravel Crypt
    // 2. Coba decrypt via format CryptoJS ("Salted__" OpenSSL)
    // 3. Coba decrypt format standar Base64(IV + Ciphertext)
    // 4. Fallback plaintext jika bukan ciphertext
}
```

**[TUNJUKKAN]**
- Konstruktor → kunci dari `SECURITY_PAYLOAD_KEY` atau fallback `APP_KEY` (SHA-256 → 32 byte untuk AES-256)
- `encrypt()` → IV random 16 byte + `openssl_encrypt AES-256-CBC` → `Base64(IV‖ciphertext)`
- `decrypt()` → coba Laravel Crypt → format CryptoJS `Salted__` → IV-prepended Base64 → fallback plaintext

**[NARASI]**
> "SecurityService adalah jantung Security Test #1. Kunci diturunkan dari environment variable SECURITY_PAYLOAD_KEY lewat SHA-256 sehingga backend dan frontend setuju pada byte kunci yang sama tanpa menukar kunci raw lewat jaringan. Setiap enkripsi memakai IV acak — dua plaintext identik menghasilkan ciphertext berbeda setiap kali, mencegah pola. Decrypt menerima tiga format sekaligus: payload dari Web Crypto frontend, CryptoJS dari Postman, dan Laravel Crypt — plus fallback plaintext untuk testing, jadi API tetap fleksibel."

**[POIN]** Spec: *"Encript username/email dan password pada payload saat kirim ke API BE"*.

---

### STEP 8 — `app/Services/CaptchaService.php` + `config/captcha.php` (±1:30)

**[FILE]** `app/Services/CaptchaService.php`, `config/captcha.php`

**[KODE UTAMA]**
```php
// Simpan di Cache: SHA-256(code) | IP peminta
Cache::put($this->cacheKey($token), hash('sha256', $code) . '|' . $ip, $this->ttl());

// Verifikasi: Token langsung dihapus (one-time) + hash_equals anti timing attack
$stored = Cache::pull($this->cacheKey($token));
return hash_equals($expectedHash, hash('sha256', strtoupper(trim($code))));
```

**[TUNJUKKAN]**
- `generate()` → kode 5 karakter dari charset tanpa I/O/0/1 → disimpan **ter-hash**: `SHA-256(code)|ip`, key cache = `SHA-256(token)`
- `renderImage()` → GD: canvas 150×50, 250 noise dot, tiap karakter `imagerotate` acak ±35°, 4 noise line → PNG data-URI
- `verify()` → token dihapus **sebelum** hasil dikembalikan (one-time), `hash_equals`, cek IP binding
- Config: `debug_enabled` default false, `bind_ip`, `ttl`

**[NARASI]**
> "CaptchaService punya empat lapis keamanan. Satu — kode tidak pernah dikirim sebagai teks: renderImage menggambar tiap karakter dengan rotasi acak plus noise, bot tidak bisa membacanya dari response JSON. Dua — storage aman: backend hanya menyimpan hash SHA-256 dari kode dan token — kalaupun cache bocor, tidak ada yang terbaca, dan perbandingan memakai hash_equals yang tahan timing attack. Tiga — token one-time: dihapus dari cache pada percobaan pertama, dikombinasikan dengan rate limit 10 login per menit membuat brute-force tidak praktis. Empat — IP binding: token hanya valid dari IP yang sama saat digenerate. Mode debug untuk testing manual butuh dua syarat: non-production DAN flag eksplisit CAPTCHA_DEBUG_ENABLED — kesalahan set APP_ENV di production tidak akan membocorkan kode."

**[POIN]** Keamanan captcha + negative test login.

---

# BAGIAN 5 — REQUEST (Validasi & Dekripsi)

### STEP 9 — `app/Http/Requests/Auth/LoginRequest.php` (±1:00)

**[FILE]** `app/Http/Requests/Auth/LoginRequest.php`

**[KODE UTAMA]**
```php
protected function prepareForValidation(): void
{
    $security = app(SecurityService::class);
    $this->merge([
        'username' => $security->decrypt((string) $this->input('user_name', $this->input('username'))),
        'password' => $security->decrypt((string) $this->input('password')),
        'captcha_code' => $security->decrypt((string) $this->input('captcha_code')),
    ]);
}
```

**[TUNJUKKAN]**
- `prepareForValidation` → dekripsi `user_name`/`username`, `email`, `password`, **`captcha_code`** via SecurityService
- `rules()` → `captcha_code` & `tokenCaptcha` **required**
- `withValidator` → verifikasi captcha; skip jika field wajib sudah error (pesan tidak dobel)
- `failedValidation` → response 422 format `{success, message, errors}` konsisten

**[NARASI]**
> "Semua dekripsi terjadi di FormRequest, bukan controller — ini pola yang memisahkan kriptografi dari business logic. Payload terenkripsi dari frontend didekripsi di sini, captcha_code ikut didekripsi juga. Lalu verifikasi captcha jalan di hook validator; kalau captcha kosong hanya muncul pesan 'wajib diisi' — bukan dua pesan dobel. Semua kegagalan validasi berformat 422 yang sama di seluruh API."

**[POIN]** Payload terenkripsi + captcha wajib + konsistensi error.

---

### STEP 10 — `StorePasienRequest.php` (±45 dtk)

**[FILE]** `app/Http/Requests/Pasien/StorePasienRequest.php`

**[KODE UTAMA]**
```php
public function rules(): array
{
    return [
        'no_rekam_medik' => ['nullable', 'string', 'max:50', 'regex:/^RM[0-9]+$/i', 'unique:pasiens,no_rekam_medik'],
        'nama_pasien' => ['required', 'string', 'min:2', 'max:255'],
        'nik' => ['required', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
        'alamat' => ['required', 'string', 'min:3'],
    ];
}

public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator): void {
        if ($this->filled('nik')) {
            $hash = hash('sha256', trim((string) $this->input('nik')));
            if (Pasien::where('nik_hash', $hash)->exists()) {
                $validator->errors()->add('nik', 'NIK sudah terdaftar dalam sistem.');
            }
        }
    });
}
```

**[TUNJUKKAN]**
- NIK: `regex:/^[0-9]+$/`, min 10, max 20, cek **duplikat via nik_hash** di `withValidator`
- No RM opsional: `regex:/^RM[0-9]+$/` + unique
- `prepareForValidation` → dekripsi NIK bila terenkripsi, alias `nama` → `nama_pasien`

**[NARASI]**
> "Validasi pendaftaran pasien: NIK wajib 10 sampai 20 digit angka, dan duplikatnya dicek lewat blind index hash — tanpa mendekripsi database. No Rekam Medik boleh dikosongkan untuk auto-generate, tapi kalau diisi manual harus format RM diikuti angka dan unik."

**[POIN]** Bonus negative test: NIK duplikat/format, No RM duplikat/format.

---

### STEP 11 — `StoreKunjunganRequest.php` (±45 dtk)

**[FILE]** `app/Http/Requests/Kunjungan/StoreKunjunganRequest.php`

**[KODE UTAMA]**
```php
protected function prepareForValidation(): void
{
    // Catatan #3: Jika kirim no_rekam_medik tanpa pasien_id, otomatis resolve pasien_id
    if (! $this->has('pasien_id') && $this->filled('no_rekam_medik')) {
        $pasien = Pasien::where('no_rekam_medik', $this->input('no_rekam_medik'))->first();
        if ($pasien) {
            $this->merge(['pasien_id' => $pasien->id]);
        }
    }
}

public function rules(): array
{
    return [
        'pasien_id' => ['required_without:no_rekam_medik', 'integer', 'exists:pasiens,id'],
        'no_rekam_medik' => ['required_without:pasien_id', 'string', 'exists:pasiens,no_rekam_medik'],
        'tanggal_kunjungan' => ['nullable', 'date'],
        'keluhan' => ['nullable', 'string', 'max:1000'],
        'catatan' => ['nullable', 'string', 'max:1000'],
    ];
}
```

**[TUNJUKKAN]**
- Rules: `pasien_id` **required_without** `no_rekam_medik`, dan sebaliknya — cukup satu saja
- `prepareForValidation` → jika kirim `no_rekam_medik`, resolve `pasien_id` otomatis
- `no_rekam_medik.exists:pasiens,no_rekam_medik` → tolak No RM yang tak terdaftar

**[NARASI]**
> "Inilah yang membuat pembuatan kunjungan cukup dengan No Rekam Medik — poin Catatan nomor 3. Klien tidak perlu tahu ID internal pasien. FormRequest menerima salah satu dari dua identifier, resolve sendiri, dan validasi exists menolak No RM yang tidak terdaftar dengan pesan jelas."

**[POIN]** Catatan #3 + negative test pasien tidak ditemukan.

---

# BAGIAN 6 — CONTROLLER (Orkestrasi Tipis)

### STEP 12 — `AuthController` + `CaptchaController` + `SecurityController` (±1:00)

**[FILE]** `app/Http/Controllers/Api/AuthController.php`, `CaptchaController.php`, `SecurityController.php`

**[TUNJUKKAN]**
- `AuthController@login` → cari user by username **atau** `email_hash` → `Hash::check` → `createToken`
- `CaptchaController@generate` → `generate(ip)` → kirim `captcha_image` + `tokenCaptcha` (kode plaintext HANYA jika `?debug=1` lolos double gate)
- `SecurityController@encrypt` → utilitas testing

**[NARASI]**
> "Controller-kontroller ini tipis — mereka hanya mengorkestrasi service dan request. Login mencari user lewat username atau blind index email, memverifikasi hash password, lalu mengeluarkan token Sanctum. CaptchaController mengikat token ke IP peminta dan tidak pernah mengembalikan kode plaintext kecuali double gate debug lolos."

**[POIN]** Controller tipis = mudah dites.

---

### STEP 13 — `PasienController` + `KunjunganController` + Resources (±1:30)

**[FILE]** `app/Http/Controllers/Api/PasienController.php`, `KunjunganController.php`, `app/Http/Resources/PasienResource.php`, `KunjunganResource.php`

**[KODE UTAMA]**
```php
// PasienController.php
public function index(Request $request): JsonResponse
{
    // Catatan #1: withCount agregat langsung dari tabel kunjungans (Anti N+1)
    $query = Pasien::query()->withCount('kunjungans as jml_kunjungan');

    if ($search = $request->input('search')) {
        $searchHash = hash('sha256', trim((string) $search));
        $query->where(fn ($q) => $q->where('nama_pasien', 'like', "%{$search}%")
            ->orWhere('no_rekam_medik', 'like', "%{$search}%")
            ->orWhere('nik_hash', $searchHash));
    }

    return response()->json([
        'success' => true,
        'message' => 'Daftar pasien berhasil diambil',
        'data' => PasienResource::collection($query->get()),
    ]);
}

// Catatan #3: Endpoint khusus modal Buat Kunjungan
public function findByRekamMedik(string $no_rekam_medik): JsonResponse
{
    $pasien = Pasien::where('no_rekam_medik', $no_rekam_medik)
        ->withCount('kunjungans as jml_kunjungan')
        ->firstOrFail();

    return response()->json([
        'success' => true,
        'message' => 'Pasien ditemukan',
        'data' => new PasienResource($pasien),
    ]);
}
```

**[TUNJUKKAN]**
- `index` → `withCount('kunjungans as jml_kunjungan')` + pencarian nama/No RM/`nik_hash`
- `findByRekamMedik` → endpoint khusus modal Buat Kunjungan (404 jika tidak ada)
- `store` → `Pasien::create` (No RM otomatis dari event model)
- `KunjunganController@index` → filter by `pasien_id` **atau** `no_rekam_medik`
- Resource → format output JSON konsisten: `jml_kunjungan`, `kunjungans` hanya saat loaded

**[NARASI]**
> "Pendaftaran pasien cukup Pasien::create — nomor rekam medik otomatis dari event model yang tadi. Daftar pasien menghitung jumlah kunjungan dengan withCount — satu query agregat, anti N+1 — dan pencarian mendukung nama, No RM, bahkan NIK lewat blind index. Endpoint search khusus memudahkan modal Buat Kunjungan. API Resources menormalisasi semua output — field jml_kunjungan dan riwayat kunjungan hanya muncul saat dibutuhkan."

**[POIN]** Catatan #1–#3 selesai dari sisi backend.

---

# BAGIAN 7 — DEMO LIVE + PENUTUP

### STEP 14 — Demo Live Singkat (±2:00) ⭐

**[AKSI — berurutan]**
1. Frontend login → **DevTools → Network → Payload**: `captcha_code`, `user_name`, `password` ciphertext; `tokenCaptcha` plain UUID
2. Dashboard: JML KUNJUNGAN RM0001 = 8, RM0002 = 0
3. Tambah Pasien → muncul **RM0003**
4. Buat Kunjungan → ketik `RM0002` → data pasien muncul → submit → JML KUNJUNGAN = 1
5. Postman: login tanpa captcha → 422; NIK duplikat → 422

**[NARASI]**
> "Bukti seluruh kode tadi jalan: payload terenkripsi persis contoh spesifikasi, No RM otomatis, kunjungan cukup dengan No Rekam Medik, jumlah kunjungan dinamis, dan validasi menolak input tidak sesuai."

---

### STEP 15 — Test Suite + Penutup (±30 dtk)

**[AKSI]** `php artisan test --compact` → **50 tests, 169 assertions**

**[NARASI]**
> "50 test, 169 assertion — mencakup seluruh lapisan yang baru saja saya jelaskan: model, service, request, controller, plus 23 negative test. Semua file PHP diformat Laravel Pint sesuai PSR-12. Terima kasih — saya siap untuk pertanyaan lebih dalam di bagian manapun dari kode ini."

---

## ✅ Checklist Pra-Rekam

```bash
# Terminal 1 — backend
php artisan migrate:fresh --seed
php artisan serve

# Terminal 2 — frontend
cd ../frontend-medisin && npm run dev

# Postman → import Medisin_API.postman_collection.json
# IDE → buka file: migrations, routes/api.php, Models, Services, Requests, Controllers
# Browser → DevTools open, tab Network, Preserve log
```

## 💡 Tips Menjelaskan Kode di Video

1. **Buka file sesuai urutan eksekusi** (migrasi → route → model → service → request → controller) — ceritanya logis: "dari database ke endpoint".
2. **Highlight baris sebelum bicara** — jangan tunjuk file mentah tanpa arahan.
3. Gunakan kata "poin spesifikasi" saat menyentuh kode yang menjawab Catatan — interviewer langsung tahu mana yang dituntut.
4. Jika waktu kurang, STEP 3, 6, 10 boleh dipangkas (pola sudah dijelaskan di STEP 2, 5, 9).
