# Bank Sampah Digital (PHP)

Sistem informasi (landing page + halaman admin) dengan **login memakai private key**
dan seluruh data tersimpan **terenkripsi** (AES-256-GCM + RSA-3072 OAEP). Tanpa framework.

## Kebutuhan
PHP 8.1+ dengan ekstensi `openssl`, `pdo_sqlite`, `mbstring` (semua ada di XAMPP/Laragon standar).

## Menjalankan
```bash
php tools/seed.php --reset      
php -S 127.0.0.1:8000 -t public public/index.php    
php tests/run_tests.php         
```
Login admin: `/admin/login` -> tempel / pilih file `keys/private_key.pem`
(`DEMO_private_key.pem` pada paket ZIP adalah kunci demo yang cocok dengan database contoh;
salin ke `keys/private_key.pem` atau pilih langsung saat login).

## Alur keamanan
1. Landing page: pendaftaran dienkripsi dengan **public key** sebelum masuk database.
2. Admin login: memasukkan **private key** (bukan password); divalidasi dengan membuka blob uji.
3. PHP tidak punya memori bersama antar-request, sehingga private key disimpan di berkas sesi
   **dalam bentuk terenkripsi AES-256-GCM**; kuncinya hanya ada di cookie HttpOnly browser admin.
   Berkas sesi saja tidak cukup. Logout / 15 menit tidak aktif menghapus keduanya.
4. Dashboard admin mendekripsi seluruh record (pendaftaran, nasabah, setoran).

## Struktur
- `public/index.php` router, `src/Crypto.php` kriptografi, `src/Database.php`, `src/Auth.php`
- `views/` tampilan, `database/schema.sql`, `database/banksampah.db` (isi: ciphertext)
- `tools/seed.php`, `tools/make_evidence.php`, `tests/run_tests.php`

> Jangan commit `keys/private_key.pem` ke repositori publik (sudah di `.gitignore`).
> Tanpa private key, data tidak dapat dipulihkan. Gunakan HTTPS pada penggunaan nyata.
> Catatan teknis: `openssl_public_encrypt()` PHP memakai OAEP-SHA1 untuk membungkus kunci AES
> acak 256-bit; ini tetap aman untuk tujuan tersebut.
