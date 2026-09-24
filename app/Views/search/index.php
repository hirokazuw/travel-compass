<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<!doctype html>
<html lang="ja" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= SearchView::escape($page->seo['title']) ?></title>
<meta name="description" content="<?= SearchView::escape($page->seo['description']) ?>">
<meta name="robots" content="<?= SearchView::escape($page->seo['robots']) ?>">
<link rel="canonical" href="<?= SearchView::escape($page->seo['canonicalUrl']) ?>">
<meta property="og:title" content="<?= SearchView::escape($page->seo['title']) ?>">
<meta property="og:description" content="<?= SearchView::escape($page->seo['description']) ?>">
<meta property="og:url" content="<?= SearchView::escape($page->seo['canonicalUrl']) ?>">
<meta property="og:type" content="<?= SearchView::escape($page->seo['ogType']) ?>">
<meta property="og:image" content="<?= SearchView::escape($page->seo['ogImageUrl']) ?>">
<meta property="og:image:width" content="1730">
<meta property="og:image:height" content="909">
<meta property="og:image:alt" content="Travel Compassの旅行検索サービス">
<meta property="og:site_name" content="<?= SearchView::escape($page->appName) ?>">
<meta property="og:locale" content="ja_JP">
<meta name="twitter:card" content="<?= SearchView::escape($page->seo['twitterCard']) ?>">
<meta name="twitter:title" content="<?= SearchView::escape($page->seo['title']) ?>">
<meta name="twitter:description" content="<?= SearchView::escape($page->seo['description']) ?>">
<meta name="twitter:image" content="<?= SearchView::escape($page->seo['ogImageUrl']) ?>">
<meta name="twitter:image:alt" content="Travel Compassの旅行検索サービス">
<meta name="csrf-token" content="<?= SearchView::escape($page->csrfToken) ?>">
<script type="application/ld+json"><?= $page->seo['structuredData'] ?></script>
<link rel="stylesheet" href="public/assets/app.css?v=<?= SearchView::escape($page->cssVersion) ?>">
<link rel="stylesheet" href="public/assets/ferry-map.css?v=<?= SearchView::escape($page->ferryMapCssVersion) ?>">
</head><body data-search-outcome="<?= SearchView::escape($page->searchOutcome) ?>">
<header><a class="site-home-link" href="https://hirokazu-watabe.jp/travel-compass/">✈ <?= SearchView::escape($page->appName) ?></a><span>旅をもっとシンプルに</span></header><main>
<p class="affiliate-disclosure">広告・PRを含みます</p>
<section class="hero hero-visual">
<img src="public/assets/og-travel-compass.png" width="1730" height="909" alt="" aria-hidden="true" fetchpriority="high">
</section>
<div class="search-introduction">
<h1>航空券・ホテル・フェリーを探す</h1>
<p>探したいものを選んで、行き先や日程などの条件を指定してください。</p>
</div>
<?php echo SearchView::render($page, 'partials/search-panel'); ?>
<?php echo SearchView::render($page, 'partials/flight-results'); ?>
<?php echo SearchView::render($page, 'partials/hotel-results'); ?>
<?php echo SearchView::render($page, 'partials/ferry-results'); ?>
<?php echo SearchView::render($page, 'partials/recent-searches'); ?>
<?php echo SearchView::render($page, 'partials/service-guide'); ?>
<?php echo SearchView::render($page, 'partials/search-loading'); ?>
</main><footer class="site-footer"><div class="site-footer-inner"><div><strong>✈ <?= SearchView::escape($page->appName) ?></strong><p>Travel Compass（トラベルコンパス）は、航空券・ホテルの比較とフェリー航路検索ができる旅行検索サービスです。</p></div><div class="site-footer-credit"><span>Created by Hirokazu WATABE · v<?= SearchView::escape($page->appVersion) ?></span><small>© <?= date('Y') ?> Hirokazu WATABE</small></div></div></footer><script src="//statics.a8.net/a8link/a8linkmgr.js"></script><script>
a8linkmgr({
  "config_id": "mENmBoJBInmbobSt2c0A"
});
</script><script type="text/javascript" language="javascript">
    var vc_pid = "892680790";
</script><script type="text/javascript" src="//aml.valuecommerce.com/vcdal.js" async></script><script type="module" src="public/assets/app.js?v=<?= SearchView::escape($page->jsVersion) ?>"></script></body></html>
