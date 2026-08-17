<?php $h = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($appName) ?></title><meta name="csrf-token" content="<?= $h($_SESSION['csrf']) ?>"><link rel="stylesheet" href="public/assets/app.css?v=20"></head><body>
<header><a class="site-home-link" href="https://hirokazu-watabe.jp/travel-compass/">✈ <?= $h($appName) ?></a><span>旅をもっとシンプルに</span></header><main>
<section class="hero"><small>PLAN YOUR NEXT JOURNEY</small><h1>旅の比較を、<br>ひとつの画面から。</h1><p>条件を入力して、航空券やホテルの候補を比較できます。</p></section>
<?php require __DIR__ . '/partials/search-panel.php'; ?>
<?php require __DIR__ . '/partials/flight-results.php'; ?>
<?php require __DIR__ . '/partials/google-hotel-results.php'; ?>
<?php require __DIR__ . '/partials/rakuten-results.php'; ?>
<?php require __DIR__ . '/partials/recent-searches.php'; ?>
</main><footer class="site-footer"><div class="site-footer-inner"><div><strong>✈ <?= $h($appName) ?></strong><p>航空券とホテルを比較できる旅行検索サービス</p></div><div class="site-footer-credit"><span>Created by Hirokazu WATABE · v<?= $h($appVersion) ?></span><small>© <?= date('Y') ?> Hirokazu WATABE</small></div></div></footer><script class="eg-widgets-script" src="https://creator.expediagroup.com/products/widgets/assets/eg-widgets.js"></script><script src="public/assets/app.js?v=<?= $h($appVersion) ?>"></script></body></html>
