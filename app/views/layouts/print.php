<!DOCTYPE html>
<html lang="vi"><head><meta charset="utf-8"><title><?= e($title ?? '') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>body{background:#fff;font-family:"Times New Roman",serif;font-size:14pt}.sheet{max-width:210mm;margin:auto;padding:15mm}</style>
</head><body>
<div class="no-print text-center p-2 bg-light border-bottom"><button class="btn btn-primary btn-sm" onclick="window.print()">In / Lưu PDF</button></div>
<div class="sheet"><?= $content ?></div>
</body></html>
