<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<?php if($page->ferry->ferryStatus !== 'idle' && $page->ferry->ferryErrors === []): ?>
<section class="result ferry-result" data-ferry-tab-content<?= $page->activeTab !== 'ferry' ? ' hidden' : '' ?>>
    <small>FERRY ROUTES</small>
    <h2><?= SearchView::escape($page->ferry->ferryValues['ferry_company_name']) ?></h2>
    <p class="price-note">運賃・ダイヤ・運航状況は参考情報です。最新情報・空席状況は各フェリー会社公式サイトでご確認ください。</p>
    <?php if($page->ferry->ferryRoutes): ?>
    <div class="ferry-routes" aria-label="フェリー航路検索結果">
        <?php foreach($page->ferry->ferryRoutes as $route): ?>
        <article class="ferry-card<?= $route['destination_url'] !== '' ? ' is-clickable' : '' ?>">
            <?php if($route['destination_url'] !== ''): ?><a class="ferry-card-content" href="<?= SearchView::escape($route['destination_url']) ?>" target="_blank" rel="noopener" aria-label="<?= SearchView::escape($route['company_name']) ?>の予約先を新しいタブで開く"><?php else: ?><div class="ferry-card-content"><?php endif ?>
                <div class="ferry-card-main">
                    <h3><?= SearchView::escape($route['company_name']) ?></h3>
                    <?php if($route['route_name'] !== ''): ?><p class="ferry-route-name"><?= SearchView::escape($route['route_name']) ?></p><?php endif ?>
                    <strong class="ferry-ports"><?= SearchView::escape($route['departure_port']) ?> → <?= SearchView::escape($route['arrival_port']) ?></strong>
                    <div class="ferry-details">
                        <?php if($route['duration'] !== ''): ?><span>所要時間：<?= SearchView::escape($route['duration']) ?></span><?php endif ?>
                        <span>車両積載：<?= $route['vehicle_available'] ? '可' : '不可' ?></span>
                        <span><?= $route['overnight'] ? '夜行便' : '昼行便' ?></span>
                    </div>
                </div>
                <?php if($route['fare_from'] !== '' || $route['fare_updated'] !== ''): ?>
                <div class="ferry-fare">
                    <?php if($route['fare_from'] !== ''): ?>
                    <small>参考運賃</small>
                    <strong><?= $route['fare_currency'] === 'JPY' ? '' : SearchView::escape($route['fare_currency']) . ' ' ?><?= SearchView::escape($route['fare_from']) ?><?= $route['fare_currency'] === 'JPY' ? '円' : '' ?>〜</strong>
                    <?php endif ?>
                    <?php if($route['fare_updated'] !== ''): ?><span><?= SearchView::escape($route['fare_updated']) ?></span><?php endif ?>
                </div>
                <?php endif ?>
            <?= $route['destination_url'] !== '' ? '</a>' : '</div>' ?>
        </article>
        <?php endforeach ?>
    </div>
    <?php elseif($page->ferry->ferryStatus === 'empty'): ?>
    <div class="flight-offers-message">条件に一致するフェリー航路が見つかりませんでした。</div>
    <?php elseif($page->ferry->ferryStatus === 'error'): ?>
    <div class="flight-offers-message">フェリー航路を検索できませんでした。時間をおいて再度お試しください。</div>
    <?php endif ?>
</section>
<?php endif ?>
