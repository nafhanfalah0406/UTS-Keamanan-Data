<?php
declare(strict_types=1);

/**
 * Front controller Bank Sampah Digital.
 *   php -S 127.0.0.1:8000 -t public public/index.php
 * (di Apache/XAMPP, .htaccess mengarahkan semua permintaan ke berkas ini)
 */

// Server bawaan PHP: biarkan berkas statis (css) dilayani langsung.
if (PHP_SAPI === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $file = realpath(__DIR__ . $reqPath);
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
        && basename($file) !== 'index.php') {
        return false;
    }
}

require __DIR__ . '/../src/bootstrap.php';

// Basis URL otomatis (mis. /banksampah/public pada XAMPP); kosong untuk server bawaan.
$base = PHP_SAPI === 'cli-server' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
define('BASE_URL', $base);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];

security_headers($path);
start_session();
if ($method === 'POST') {
    csrf_check();
}

/** Halaman yang butuh login admin: kembalikan private key sesi atau alihkan ke login. */
function require_admin()
{
    $key = Auth::key();
    if ($key === null) {
        flash('warn', 'Sesi berakhir atau belum login. Masukkan private key.');
        redirect(url('/admin/login'));
    }
    return $key;
}

try {
    // ============================================================ landing (publik)
    if ($path === '/' && $method === 'GET') {
        render('landing', ['harga' => HARGA]);
        exit;
    }

    if ($path === '/daftar' && $method === 'POST') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $hp = trim((string) ($_POST['hp'] ?? ''));
        $alamat = trim((string) ($_POST['alamat'] ?? ''));
        $pesan = trim((string) ($_POST['pesan'] ?? ''));
        if (!len_between($nama, 2, 80) || !valid_hp($hp) || !len_between($alamat, 5, 200) || mb_strlen($pesan) > 300) {
            flash('err', 'Data pendaftaran tidak valid. Periksa kembali isian Anda.');
            redirect(url('/') . '#daftar');
        }
        // Dienkripsi dengan PUBLIC KEY sebelum menyentuh database
        Database::insertRecord('pendaftaran', [
            'nama' => $nama, 'hp' => $hp, 'alamat' => $alamat, 'pesan' => $pesan, 'tanggal' => now_str(),
        ], Database::publicKey());
        flash('ok', 'Terima kasih! Pendaftaran Anda tersimpan terenkripsi dan akan diverifikasi petugas.');
        redirect(url('/') . '#daftar');
    }

    // ===================================================== login admin (PRIVATE KEY)
    if ($path === '/admin/login') {
        $fp = Database::meta('fingerprint');
        if ($method === 'GET') {
            render('login', ['fingerprint' => $fp], false, 'Masuk Admin - Bank Sampah Digital');
            exit;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '?';
        if (Auth::tooManyFails($ip)) {
            http_response_code(429);
            flash('err', 'Terlalu banyak percobaan gagal. Coba lagi beberapa menit lagi.');
            render('login', ['fingerprint' => $fp], false, 'Masuk Admin - Bank Sampah Digital');
            exit;
        }
        if (!Auth::attempt((string) ($_POST['private_key'] ?? ''))) {
            Auth::recordFail($ip);
            http_response_code(401);
            flash('err', 'Private key tidak valid atau tidak cocok dengan sistem.');
            render('login', ['fingerprint' => $fp], false, 'Masuk Admin - Bank Sampah Digital');
            exit;
        }
        Auth::clearFails($ip);
        redirect(url('/admin'));
    }

    if ($path === '/admin/logout' && $method === 'POST') {
        Auth::logout();   // buang kunci sesi + private key terenkripsi
        flash('ok', 'Anda telah keluar. Private key dihapus dari sesi.');
        redirect(url('/admin/login'));
    }

    // ================================ halaman admin (semua konten didekripsi)
    if ($path === '/admin' && $method === 'GET') {
        $priv = require_admin();
        $pendaftaran = Database::loadAll('pendaftaran', $priv);
        $nasabah = Database::loadAll('nasabah', $priv);
        $setoran = Database::loadAll('setoran', $priv);

        $namaById = [];
        foreach ($nasabah as $n) {
            $namaById[$n['id']] = $n['nama'] ?? '?';
        }
        $saldo = [];
        foreach ($setoran as $i => $s) {
            if (!empty($s['_error'])) {
                continue;
            }
            $saldo[$s['nasabah_id']] = ($saldo[$s['nasabah_id']] ?? 0) + $s['total'];
            $setoran[$i]['nasabah_nama'] = $namaById[$s['nasabah_id']] ?? '(dihapus)';
        }
        foreach ($nasabah as $i => $n) {
            $nasabah[$i]['saldo'] = $saldo[$n['id']] ?? 0;
        }
        $valid = array_filter($setoran, fn($s) => empty($s['_error']));
        $stats = [
            'nasabah' => count($nasabah),
            'pendaftaran' => count($pendaftaran),
            'berat' => array_sum(array_column($valid, 'berat')),
            'nilai' => array_sum(array_column($valid, 'total')),
        ];
        $inspector = [];
        foreach (TABLES as $t) {
            foreach (Database::pdo()->query("SELECT id, payload FROM $t ORDER BY id LIMIT 4") as $r) {
                $inspector[] = ['tabel' => $t, 'id' => $r['id'], 'panjang' => strlen($r['payload']),
                                'cipher' => substr($r['payload'], 0, 96) . '…'];
            }
        }
        render('admin', [
            'pendaftaran' => $pendaftaran, 'nasabah' => $nasabah, 'setoran' => $setoran,
            'stats' => $stats, 'harga' => HARGA, 'inspector' => $inspector,
            'fingerprint' => Database::meta('fingerprint'), 'ttl' => intdiv(Auth::ttlLeft(), 60),
        ], true, 'Dashboard Admin - Bank Sampah Digital');
        exit;
    }

    if ($path === '/admin/nasabah' && $method === 'POST') {
        require_admin();
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $hp = trim((string) ($_POST['hp'] ?? ''));
        $alamat = trim((string) ($_POST['alamat'] ?? ''));
        if (!len_between($nama, 2, 80) || !valid_hp($hp) || !len_between($alamat, 5, 200)) {
            flash('err', 'Data nasabah tidak valid.');
        } else {
            Database::insertRecord('nasabah', ['nama' => $nama, 'hp' => $hp, 'alamat' => $alamat,
                                               'tanggal' => now_str()], Database::publicKey());
            flash('ok', 'Nasabah ditambahkan (tersimpan terenkripsi).');
        }
        redirect(url('/admin') . '#nasabah');
    }

    if ($path === '/admin/setoran' && $method === 'POST') {
        $priv = require_admin();
        $nid = filter_var($_POST['nasabah_id'] ?? null, FILTER_VALIDATE_INT);
        $jenis = (string) ($_POST['jenis'] ?? '');
        $berat = filter_var($_POST['berat'] ?? null, FILTER_VALIDATE_FLOAT);
        $ids = array_column(array_filter(Database::loadAll('nasabah', $priv), fn($n) => empty($n['_error'])), 'id');
        if ($nid === false || $berat === false || !isset(HARGA[$jenis]) || $berat < 0.1 || $berat > 1000
            || !in_array($nid, $ids, true)) {
            flash('err', 'Data setoran tidak valid.');
        } else {
            $berat = round($berat, 2);
            $harga = HARGA[$jenis];
            Database::insertRecord('setoran', [
                'nasabah_id' => $nid, 'jenis' => $jenis, 'berat' => $berat, 'harga' => $harga,
                'total' => (int) round($berat * $harga),
                'catatan' => mb_substr(trim((string) ($_POST['catatan'] ?? '')), 0, 200),
                'tanggal' => now_str(),
            ], Database::publicKey());
            flash('ok', 'Setoran dicatat (tersimpan terenkripsi).');
        }
        redirect(url('/admin') . '#setoran');
    }

    if (preg_match('#\A/admin/pendaftaran/(\d+)/terima\z#', $path, $m) && $method === 'POST') {
        $priv = require_admin();
        $stmt = Database::pdo()->prepare('SELECT payload FROM pendaftaran WHERE id = ?');
        $stmt->execute([(int) $m[1]]);
        $payload = $stmt->fetchColumn();
        if ($payload === false) {
            abort(404, 'Pendaftaran tidak ditemukan');
        }
        $d = Crypto::decryptRecord($priv, (string) $payload, 'pendaftaran');
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        Database::insertRecord('nasabah', ['nama' => $d['nama'], 'hp' => $d['hp'], 'alamat' => $d['alamat'],
                                           'tanggal' => now_str()], Database::publicKey());
        $pdo->prepare('DELETE FROM pendaftaran WHERE id = ?')->execute([(int) $m[1]]);
        $pdo->commit();
        flash('ok', 'Pendaftar diterima menjadi nasabah.');
        redirect(url('/admin') . '#nasabah');
    }

    if (preg_match('#\A/admin/hapus/([a-z]+)/(\d+)\z#', $path, $m) && $method === 'POST') {
        require_admin();
        if (!in_array($m[1], TABLES, true)) {
            abort(404, 'Tabel tidak ditemukan');
        }
        Database::pdo()->prepare("DELETE FROM {$m[1]} WHERE id = ?")->execute([(int) $m[2]]);
        flash('ok', 'Data dihapus.');
        redirect(url('/admin'));
    }

    abort(404, 'Halaman tidak ditemukan');
} catch (Throwable $e) {
    error_log('[BankSampah] ' . $e->getMessage());
    abort(500, 'Terjadi kesalahan pada server');
}
