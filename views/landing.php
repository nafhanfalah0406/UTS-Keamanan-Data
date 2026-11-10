<header class="hero"><div class="wrap">
  <h1>Ubah sampah jadi <span>tabungan</span>, data Anda tetap rahasia.</h1>
  <p>Setor sampah terpilah ke bank sampah lingkungan Anda, kumpulkan saldo, dan
     tenang: identitas serta riwayat setoran disimpan dalam bentuk terenkripsi.</p>
  <div class="cta">
    <a class="btn alt" href="#daftar">Daftar jadi nasabah</a>
    <a class="btn ghost" style="color:#fff;border-color:#fff" href="#harga">Lihat harga sampah</a>
  </div>
</div></header>

<section class="block" id="cara"><div class="wrap">
  <h2>Cara kerja</h2>
  <p class="sub">Empat langkah sederhana untuk mulai menabung sampah.</p>
  <div class="grid">
    <div class="card"><span class="n">1</span><h3>Daftar</h3>
      <p>Isi formulir di bawah. Data Anda langsung dienkripsi di server sebelum disimpan.</p></div>
    <div class="card"><span class="n">2</span><h3>Setor &amp; timbang</h3>
      <p>Bawa sampah terpilah ke petugas. Berat dan jenis dicatat otomatis.</p></div>
    <div class="card"><span class="n">3</span><h3>Saldo bertambah</h3>
      <p>Nilai setoran dihitung berdasarkan harga per kilogram dan masuk ke saldo Anda.</p></div>
    <div class="card"><span class="n">4</span><h3>Privasi terjaga</h3>
      <p>Hanya admin pemegang <i>private key</i> yang dapat membuka data nasabah.</p></div>
  </div>
</div></section>

<section class="block" id="harga" style="background:#ece9da"><div class="wrap">
  <h2>Harga beli sampah</h2>
  <p class="sub">Harga per kilogram untuk sampah yang sudah dipilah dan bersih.</p>
  <div class="tablewrap"><table>
    <tr><th>Jenis sampah</th><th class="r">Harga / kg</th></tr>
    <?php foreach ($harga as $jenis => $h): ?>
    <tr><td><?= e($jenis) ?></td><td class="r"><?= e(rp($h)) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div></section>

<section class="block" id="daftar"><div class="wrap" style="max-width:640px">
  <h2>Pendaftaran nasabah</h2>
  <p class="sub">Formulir ini dienkripsi dengan <i>public key</i> sistem
     <span class="pill lock-pill">AES-256-GCM + RSA-OAEP</span></p>
  <form class="stack card" method="post" action="<?= e(url('/daftar')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div><label for="nama">Nama lengkap</label>
      <input id="nama" name="nama" required minlength="2" maxlength="80"></div>
    <div class="two">
      <div><label for="hp">No. HP</label>
        <input id="hp" name="hp" required pattern="[0-9+\- ]{8,20}" placeholder="0812-xxxx-xxxx"></div>
      <div><label for="alamat">Alamat</label>
        <input id="alamat" name="alamat" required minlength="5" maxlength="200"></div>
    </div>
    <div><label for="pesan">Pesan (opsional)</label>
      <textarea id="pesan" name="pesan" rows="3" maxlength="300"></textarea></div>
    <button class="btn" type="submit">Kirim pendaftaran</button>
  </form>
</div></section>
