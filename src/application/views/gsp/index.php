<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= html_escape($title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <style> body{max-width:1024px;margin:2rem auto;} code{background:#f6f8fa;padding:.2rem .4rem;border-radius:.25rem;} </style>
</head>
<body>
  <h1><?= html_escape($title) ?></h1>

  <?php if (!empty($message)): ?>
    <article class="contrast"><strong>Info:</strong> <?= $message ?></article>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <article class="contrast" style="border-left:4px solid #d33"><strong>Kesalahan:</strong> <?= $error ?></article>
  <?php endif; ?>

  <section>
    <h3>1) Unggah Log Navigasi (CSV)</h3>
    <p>Format kolom: <code>session_id,viewed_at,page</code>. Contoh waktu: <code>2025-08-01 09:00:00</code></p>
    <form method="post" enctype="multipart/form-data" action="<?= site_url('gsp/upload') ?>">
      <input type="file" name="csv" accept=".csv" required>
      <button type="submit">Unggah</button>
    </form>
  </section>

  <section>
    <h3>2) Jalankan Mining GSP</h3>
    <form method="post" action="<?= site_url('gsp/run') ?>">
      <div class="grid">
        <label>Minimum Support
          <input type="number" name="min_support" value="2" min="1">
        </label>
        <label>Maksimum Panjang Pola
          <input type="number" name="max_len" value="5" min="1" max="10">
        </label>
      </div>
      <button type="submit">Proses</button>
    </form>
  </section>

  <section>
    <h3>Contoh CSV</h3>
    <pre>session_id,viewed_at,page
s1,2025-08-01 09:00:00,Home
s1,2025-08-01 09:00:10,Akademik
s1,2025-08-01 09:00:30,Beasiswa
s2,2025-08-02 08:10:00,Home
s2,2025-08-02 08:10:20,Akademik
s3,2025-08-02 10:00:00,Home
s3,2025-08-02 10:00:15,Beasiswa</pre>
  </section>
</body>
</html>
