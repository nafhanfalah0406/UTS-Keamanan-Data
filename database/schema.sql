-- Skema database Bank Sampah Digital (SQLite)
-- Kolom `payload` berisi BLOB TERENKRIPSI (AES-256-GCM + RSA-OAEP), bukan data asli.

CREATE TABLE IF NOT EXISTS meta (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

-- Pendaftaran calon nasabah dari landing page (publik -> terenkripsi)
CREATE TABLE IF NOT EXISTS pendaftaran (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    payload    TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Data nasabah (nama, hp, alamat terenkripsi)
CREATE TABLE IF NOT EXISTS nasabah (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    payload    TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Transaksi setoran sampah (nasabah, jenis, berat, nilai terenkripsi)
CREATE TABLE IF NOT EXISTS setoran (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    payload    TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Pembatasan percobaan login gagal (anti brute-force)
CREATE TABLE IF NOT EXISTS login_fail (
    ip      TEXT PRIMARY KEY,
    n       INTEGER NOT NULL,
    started INTEGER NOT NULL
);
