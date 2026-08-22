<?php if($ferryStatus !== 'idle' && $ferryErrors === []): ?>
<section class="result ferry-result" data-ferry-tab-content<?= $activeTab !== 'ferry' ? ' hidden' : '' ?>>
    <small>FERRY ROUTES</small>
    <h2><?= $h($ferryValues['ferry_company_name']) ?></h2>
    <?php if($ferryRoutes): ?>
    <div class="ferry-routes" aria-label="フェリー航路検索結果">
        <?php foreach($ferryRoutes as $route): ?>
        <article class="ferry-card<?= $route['destination_url'] !== '' ? ' is-clickable' : '' ?>">
            <?php if($route['destination_url'] !== ''): ?><a class="ferry-card-content" href="<?= $h($route['destination_url']) ?>" target="_blank" rel="noopener" aria-label="<?= $h($route['company_name']) ?>の予約先を新しいタブで開く"><?php else: ?><div class="ferry-card-content"><?php endif ?>
                <div class="ferry-card-main">
                    <h3><?= $h($route['company_name']) ?></h3>
                    <?php if($route['route_name'] !== ''): ?><p class="ferry-route-name"><?= $h($route['route_name']) ?></p><?php endif ?>
                    <strong class="ferry-ports"><?= $h($route['departure_port']) ?> → <?= $h($route['arrival_port']) ?></strong>
                    <div class="ferry-details">
                        <?php if($route['duration'] !== ''): ?><span>所要時間：<?= $h($route['duration']) ?></span><?php endif ?>
                        <span>車両積載：<?= $route['vehicle_available'] ? '可' : '不可' ?></span>
                        <span><?= $route['overnight'] ? '夜行便' : '昼行便' ?></span>
                    </div>
                </div>
                <?php if($route['fare_from'] !== ''): ?>
                <div class="ferry-fare">
                    <small>参考運賃</small>
                    <strong><?= $route['fare_currency'] === 'JPY' ? '' : $h($route['fare_currency']) . ' ' ?><?= $h($route['fare_from']) ?><?= $route['fare_currency'] === 'JPY' ? '円' : '' ?>〜</strong>
                    <?php if($route['fare_updated'] !== ''): ?><span><?= $h($route['fare_updated']) ?></span><?php endif ?>
                </div>
                <?php endif ?>
            <?= $route['destination_url'] !== '' ? '</a>' : '</div>' ?>
        </article>
        <?php endforeach ?>
    </div>
    <?php elseif($ferryStatus === 'empty'): ?>
    <div class="flight-offers-message">条件に一致するフェリー航路が見つかりませんでした。</div>
    <?php elseif($ferryStatus === 'error'): ?>
    <div class="flight-offers-message">フェリー航路を検索できませんでした。時間をおいて再度お試しください。</div>
    <?php endif ?>
</section>
<?php endif ?>
