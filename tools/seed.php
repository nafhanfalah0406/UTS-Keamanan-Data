<?php
declare(strict_types=1);

/**
 * Inisialisasi: buat pasangan kunci RSA, database, dan data contoh terenkripsi.
 *
 *   php tools/seed.php            # buat kunci + database + data demo
 *   php tools/seed.php --reset    # hapus database lama lalu buat ulang
 *
 * PRIVATE KEY (keys/private_key.pem) = "password" admin. Simpan di tempat aman,
 * JANGAN di-commit ke GitHub / dibagikan. Database hanya berisi public key.
 */

require __DIR__ . '/../src/bootstrap.php';

$keyDir = BASE_DIR . '/keys';
$privPath = $keyDir . '/private_key.pem';
$pubPath = $keyDir . '/public_key.pem';

$nasabah = [
    ['Siti Aminah', '0812-0000-0001', 'Jl. Melati No. 12, RT 03/RW 05'],
    ['Budi Santoso', '0812-0000-0002', 'Jl. Kenanga No. 7, RT 01/RW 02'],
    ['Dewi Lestari', '0812-0000-0003', 'Perum Griya Asri Blok C-4'],
    ['Agus Salim', '0812-0000-0004', 'Jl. Mawar No. 21, RT 06/RW 01'],
];
$setoran = [   // [index nasabah, jenis, berat kg, tanggal]
    [0, 'Plastik PET (botol)', 4.5, '2026-11-03 09:10'],
    [0, 'Kertas / Kardus', 8.0, '2026-11-05 15:40'],
    [1, 'Logam / Kaleng', 2.25, '2026-11-04 10:05'],
    [2, 'Minyak jelantah', 3.0, '2026-11-06 08:30'],
    [3, 'Kaca / Botol kaca', 12.0, '2026-11-07 13:15'],
    [1, 'Plastik PET (botol)', 6.0, '2026-11-09 11:00'],
];
$pendaftar = [
    ['Rina Marlina', '0812-0000-0010', 'Jl. Anggrek No. 3, RT 02/RW 04', 'Ingin menyetor sampah plastik tiap minggu.'],
    ['Hendra Wijaya', '0812-0000-0011', 'Jl. Flamboyan No. 15', 'Apakah menerima minyak jelantah dari warung?'],
];

$reset = in_array('--reset', $argv ?? [], true);
if (file_exists(DB_PATH)) {
    if (!$reset) {
        fwrite(STDERR, "Database sudah ada. Gunakan --reset untuk membuat ulang.\n");
        exit(1);
    }
    unlink(DB_PATH);
}
if (!is_dir($keyDir)) {
    mkdir($keyDir, 0700, true);
}
if (!file_exists($privPath)) {
    [$privPem, $pubPem] = Crypto::generateKeypair();
    file_put_contents($privPath, $privPem);
    chmod($privPath, 0600);
    file_put_contents($pubPath, $pubPem);
    echo "Kunci baru dibuat: $privPath\n";
}
$pubPem = (string) file_get_contents($pubPath);
Database::init(DB_PATH, $pubPem);

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pub = Crypto::loadPublicKey($pubPem);
$ids = [];
foreach ($nasabah as [$nama, $hp, $alamat]) {
    $ids[] = Database::insertRecord('nasabah', ['nama' => $nama, 'hp' => $hp, 'alamat' => $alamat,
                                                'tanggal' => '2026-11-01 08:00'], $pub, $pdo);
}
foreach ($setoran as [$i, $jenis, $berat, $tgl]) {
    $harga = HARGA[$jenis];
    Database::insertRecord('setoran', ['nasabah_id' => $ids[$i], 'jenis' => $jenis, 'berat' => $berat,
                                       'harga' => $harga, 'total' => (int) round($berat * $harga),
                                       'catatan' => '', 'tanggal' => $tgl], $pub, $pdo);
}
foreach ($pendaftar as [$nama, $hp, $alamat, $pesan]) {
    Database::insertRecord('pendaftaran', ['nama' => $nama, 'hp' => $hp, 'alamat' => $alamat,
                                           'pesan' => $pesan, 'tanggal' => '2026-11-09 16:20'], $pub, $pdo);
}
echo 'Database dibuat: ' . DB_PATH . "\n";
echo 'Fingerprint kunci: ' . Crypto::fingerprint($pubPem) . "\n";
