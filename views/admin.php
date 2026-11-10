<div class="wrap">
  <div class="adminhead">
    <div>
      <h1>Dashboard Admin</h1>
      <span class="pill">Terdekripsi dengan private key Anda</span>
      <span class="fp">&nbsp;Kunci: <?= e(substr($fingerprint, 0, 23)) ?>…</span>
    </div>
    <span class="fp">Sesi aktif ± <?= e($ttl) ?> menit (otomatis diperpanjang)</span>
  </div>

  <div class="stats">
    <div class="stat"><b><?= e($stats['nasabah']) ?></b><span>Nasabah</span></div>
    <div class="stat"><b><?= e($stats['pendaftaran']) ?></b><span>Pendaftaran menunggu</span></div>
    <div class="stat"><b><?= e(number_format($stats['berat'], 2, ',', '.')) ?> kg</b><span>Total sampah masuk</span></div>
    <div class="stat"><b><?= e(rp($stats['nilai'])) ?></b><span>Total nilai setoran</span></div>
  </div>

  <div class="panel" id="pendaftaran">
    <h2>Pendaftaran dari landing page <span class="pill lock-pill">dekripsi</span></h2>
    <div class="tablewrap"><table>
      <tr><th>#</th><th>Nama</th><th>No. HP</th><th>Alamat</th><th>Pesan</th><th>Tanggal</th><th></th></tr>
      <?php foreach ($pendaftaran as $p): ?>
        <?php if (!empty($p['_error'])): ?>
          <tr><td><?= e($p['id']) ?></td><td colspan="6" class="err-row">Gagal dekripsi (data rusak / kunci salah)</td></tr>
        <?php else: ?>
          <tr><td><?= e($p['id']) ?></td><td><?= e($p['nama']) ?></td><td><?= e($p['hp']) ?></td>
            <td><?= e($p['alamat']) ?></td><td><?= e($p['pesan'] !== '' ? $p['pesan'] : '-') ?></td>
            <td><?= e($p['tanggal']) ?></td>
            <td style="white-space:nowrap">
              <form class="inline" method="post" action="<?= e(url('/admin/pendaftaran/' . $p['id'] . '/terima')) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="btn sm" type="submit">Terima</button></form>
              <?= hapus_form('pendaftaran', $p['id']) ?></td></tr>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (!$pendaftaran): ?><tr><td colspan="7">Belum ada pendaftaran baru.</td></tr><?php endif; ?>
    </table></div>
  </div>

  <div class="panel" id="nasabah">
    <h2>Data nasabah <span class="pill lock-pill">dekripsi</span></h2>
    <div class="tablewrap"><table>
      <tr><th>ID</th><th>Nama</th><th>No. HP</th><th>Alamat</th><th class="r">Saldo</th><th></th></tr>
      <?php foreach ($nasabah as $n): ?>
        <?php if (!empty($n['_error'])): ?>
          <tr><td><?= e($n['id']) ?></td><td colspan="5" class="err-row">Gagal dekripsi</td></tr>
        <?php else: ?>
          <tr><td><?= e($n['id']) ?></td><td><?= e($n['nama']) ?></td><td><?= e($n['hp']) ?></td>
            <td><?= e($n['alamat']) ?></td><td class="r"><?= e(rp($n['saldo'])) ?></td>
            <td><?= hapus_form('nasabah', $n['id']) ?></td></tr>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (!$nasabah): ?><tr><td colspan="6">Belum ada nasabah.</td></tr><?php endif; ?>
    </table></div>
    <details class="add"><summary>+ Tambah nasabah</summary>
      <form class="stack" method="post" action="<?= e(url('/admin/nasabah')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="two">
          <div><label>Nama</label><input name="nama" required minlength="2" maxlength="80"></div>
          <div><label>No. HP</label><input name="hp" required pattern="[0-9+\- ]{8,20}"></div>
        </div>
        <div><label>Alamat</label><input name="alamat" required minlength="5" maxlength="200"></div>
        <div><button class="btn" type="submit">Simpan (terenkripsi)</button></div>
      </form></details>
  </div>

  <div class="panel" id="setoran">
    <h2>Riwayat setoran sampah <span class="pill lock-pill">dekripsi</span></h2>
    <div class="tablewrap"><table>
      <tr><th>#</th><th>Tanggal</th><th>Nasabah</th><th>Jenis</th><th class="r">Berat</th>
          <th class="r">Harga/kg</th><th class="r">Total</th><th></th></tr>
      <?php foreach ($setoran as $s): ?>
        <?php if (!empty($s['_error'])): ?>
          <tr><td><?= e($s['id']) ?></td><td colspan="7" class="err-row">Gagal dekripsi</td></tr>
        <?php else: ?>
          <tr><td><?= e($s['id']) ?></td><td><?= e($s['tanggal']) ?></td><td><?= e($s['nasabah_nama']) ?></td>
            <td><?= e($s['jenis']) ?></td><td class="r"><?= e(number_format($s['berat'], 2, ',', '.')) ?> kg</td>
            <td class="r"><?= e(rp($s['harga'])) ?></td><td class="r"><?= e(rp($s['total'])) ?></td>
            <td><?= hapus_form('setoran', $s['id']) ?></td></tr>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (!$setoran): ?><tr><td colspan="8">Belum ada setoran.</td></tr><?php endif; ?>
    </table></div>
    <details class="add"><summary>+ Catat setoran</summary>
      <form class="stack" method="post" action="<?= e(url('/admin/setoran')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="two">
          <div><label>Nasabah</label>
            <select name="nasabah_id" required>
              <?php foreach ($nasabah as $n): if (!empty($n['_error'])) continue; ?>
                <option value="<?= e($n['id']) ?>"><?= e($n['nama']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div><label>Jenis sampah</label>
            <select name="jenis" required>
              <?php foreach ($harga as $j => $h): ?>
                <option value="<?= e($j) ?>"><?= e($j) ?> (<?= e(rp($h)) ?>/kg)</option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <div class="two">
          <div><label>Berat (kg)</label><input name="berat" type="number" step="0.01" min="0.1" max="1000" required></div>
          <div><label>Catatan</label><input name="catatan" maxlength="200"></div>
        </div>
        <div><button class="btn" type="submit">Simpan (terenkripsi)</button></div>
      </form></details>
  </div>

  <div class="panel" id="inspektur">
    <h2>Isi database sebenarnya <span class="pill">ciphertext</span></h2>
    <p class="fp" style="margin-top:0">Inilah yang tersimpan di SQLite: tanpa private key, isinya tidak dapat dibaca.</p>
    <div class="tablewrap"><table>
      <tr><th>Tabel</th><th>ID</th><th class="r">Panjang</th><th>Blob terenkripsi (potongan)</th></tr>
      <?php foreach ($inspector as $r): ?>
      <tr><td><?= e($r['tabel']) ?></td><td><?= e($r['id']) ?></td><td class="r"><?= e($r['panjang']) ?></td>
          <td class="cipher"><?= e($r['cipher']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
