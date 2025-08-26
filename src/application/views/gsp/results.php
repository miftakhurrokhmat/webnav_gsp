<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hasil GSP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <style> body{max-width:1024px;margin:2rem auto;} table{font-variant-numeric: tabular-nums;} </style>
</head>
<body>
  <h1>Hasil Mining GSP</h1>
  <p>Total sequence: <strong><?= $num_sequences ?></strong> — Min support: <strong><?= $min_support ?></strong> — Max len: <strong><?= $max_len ?></strong></p>

  <?php if (empty($patterns)): ?>
    <article class="contrast">Tidak ada pola yang memenuhi syarat.</article>
  <?php else: ?>
    <table role="grid">
      <thead>
        <tr><th>#</th><th>Pola</th><th>Support</th><th>Panjang</th></tr>
      </thead>
      <tbody>
        <?php $i=1; foreach ($patterns as $p): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= implode(' &rarr; ', array_map('html_escape', $p['pattern'])) ?></td>
            <td><?= (int)$p['support'] ?></td>
            <td><?= count($p['pattern']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <p><a href="<?= site_url('gsp') ?>">&larr; Kembali</a></p>
</body>
</html>
