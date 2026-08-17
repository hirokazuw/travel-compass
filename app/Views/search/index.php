<?php $h = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="ja" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($seo['title']) ?></title>
<meta name="description" content="<?= $h($seo['description']) ?>">
<meta name="robots" content="<?= $h($seo['robots']) ?>">
<link rel="canonical" href="<?= $h($seo['canonicalUrl']) ?>">
<meta property="og:title" content="<?= $h($seo['title']) ?>">
<meta property="og:description" content="<?= $h($seo['description']) ?>">
<meta property="og:url" content="<?= $h($seo['canonicalUrl']) ?>">
<meta property="og:type" content="<?= $h($seo['ogType']) ?>">
<meta property="og:image" content="<?= $h($seo['ogImageUrl']) ?>">
<meta property="og:image:width" content="1730">
<meta property="og:image:height" content="909">
<meta property="og:image:alt" content="Travel Compassの航空券・ホテル比較サービス">
<meta property="og:site_name" content="<?= $h($appName) ?>">
<meta property="og:locale" content="ja_JP">
<meta name="twitter:card" content="<?= $h($seo['twitterCard']) ?>">
<meta name="twitter:title" content="<?= $h($seo['title']) ?>">
<meta name="twitter:description" content="<?= $h($seo['description']) ?>">
<meta name="twitter:image" content="<?= $h($seo['ogImageUrl']) ?>">
<meta name="twitter:image:alt" content="Travel Compassの航空券・ホテル比較サービス">
<meta name="csrf-token" content="<?= $h($_SESSION['csrf']) ?>">
<script type="application/ld+json"><?= $seo['structuredData'] ?></script>
<link rel="stylesheet" href="public/assets/app.css?v=<?= $h($cssVersion) ?>">
</head><body>
<header><a class="site-home-link" href="https://hirokazu-watabe.jp/travel-compass/">✈ <?= $h($appName) ?></a><span>旅をもっとシンプルに</span></header><main>
<section class="hero hero-visual">
<img src="public/assets/og-travel-compass.png" width="1730" height="909" alt="" aria-hidden="true" fetchpriority="high">
<div class="visually-hidden">
<small>PLAN YOUR NEXT JOURNEY</small>
<h1>旅の比較を、ひとつの画面から。</h1>
<p>条件を入力して、航空券やホテルの候補を比較できます。</p>
</div>
</section>
<?php require __DIR__ . '/partials/search-panel.php'; ?>
<?php require __DIR__ . '/partials/flight-results.php'; ?>
<?php require __DIR__ . '/partials/google-hotel-results.php'; ?>
<?php require __DIR__ . '/partials/rakuten-results.php'; ?>
<?php require __DIR__ . '/partials/recent-searches.php'; ?>
</main><footer class="site-footer"><div class="site-footer-inner"><div><strong>✈ <?= $h($appName) ?></strong><p>航空券とホテルを比較できる旅行検索サービス</p></div><div class="site-footer-credit"><span>Created by Hirokazu WATABE · v<?= $h($appVersion) ?></span><small>© <?= date('Y') ?> Hirokazu WATABE</small></div></div></footer><script class="eg-widgets-script" src="https://creator.expediagroup.com/products/widgets/assets/eg-widgets.js"></script><script src="public/assets/app.js?v=<?= $h($jsVersion) ?>"></script></body></html>
