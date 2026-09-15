<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<?php if ($page->flight->result): ?>
<section class="result" data-flight-tab-content data-flight-scope="<?= $page->flight->isDomesticFlight ? 'domestic' : 'overseas' ?>">
    <small>YOUR PLAN</small>
    <h2><?= SearchView::escape($page->flight->cityLabels['origin'] ?? $page->flight->values['origin']) ?> → <?= SearchView::escape($page->flight->cityLabels['destination'] ?? $page->flight->values['destination']) ?></h2>
    <p><?= SearchView::escape($page->flight->values['departure_date']) ?><?= $page->flight->values['return_date']?' 〜 '.SearchView::escape($page->flight->values['return_date']):'' ?>・<?= $page->flight->values['return_date']!==''?'往復':'片道' ?>・<?= SearchView::escape($page->flight->values['travelers']) ?>名</p>
    <?php if ($page->flight->flightOffers): ?>
    <h3 class="flight-result-subheading">航空会社別の参考価格</h3>
    <div class="flight-offers" aria-label="航空会社別の参考価格">
        <?php foreach($page->flight->flightOffers as $offerIndex=>$offer): ?>
        <?php $offerLogo = $offer['carrier_code'] !== '' ? 'https://pics.avs.io/120/40/' . rawurlencode($offer['carrier_code']) . '.png' : $offer['airline_logo']; ?>
        <article class="flight-offer"<?= $offerIndex>=6?' hidden data-extra-offer':'' ?>>
            <?php if($offer['official_url']!==''): ?><a class="flight-carrier flight-airline-link" href="<?= SearchView::escape($offer['official_url']) ?>" target="_blank" rel="noopener" aria-label="<?= SearchView::escape($offer['carrier_name']) ?>公式サイトを新しいタブで開く"><?php else: ?><div class="flight-carrier"><?php endif ?>
                <?php if($offerLogo!==''): ?><img src="<?= SearchView::escape($offerLogo) ?>" alt="" width="120" height="40" loading="lazy" data-airline-logo><span class="carrier-code" data-airline-logo-fallback hidden><?= SearchView::escape($offer['carrier_code']) ?></span><?php elseif($offer['carrier_code']!==''): ?><span class="carrier-code"><?= SearchView::escape($offer['carrier_code']) ?></span><?php endif ?>
                <b><?= SearchView::escape($offer['carrier_name']) ?></b>
                <?php if($offer['carrier_code']!==''): ?><small class="flight-iata-code"><?= SearchView::escape($offer['carrier_code']) ?></small><?php endif ?>
                <?php if($offer['alliance']!==''): ?><span class="flight-airline-meta"><?= SearchView::escape($offer['alliance']) ?></span><?php endif ?>
                <?php if($offer['ffp_name']!==''): ?><span class="flight-airline-meta"><?= SearchView::escape($offer['ffp_name']) ?></span><?php endif ?>
                <span class="flight-count">該当便 <?= SearchView::escape($offer['flight_count']) ?>便</span>
                <?php if($offer['direct_flight_count']>0): ?><span class="flight-stops">直行便 <?= SearchView::escape($offer['direct_flight_count']) ?>便</span><?php endif ?>
            <?= $offer['official_url']!==''?'</a>':'</div>' ?>
            <div class="flight-price"><small>参考価格</small><strong><?= $offer['currency']==='JPY'?'¥':SearchView::escape($offer['currency']).' ' ?><?= SearchView::escape($offer['price']) ?>〜</strong></div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if(count($page->flight->flightOffers)>6): ?><button type="button" class="flight-offers-toggle" aria-expanded="false">もっと見る</button><?php endif ?>
    <p class="price-note">表示価格はApifyで取得したGoogle Flightsの検索結果による参考価格です。航空会社ロゴはAviasales CDNから取得しています。実際の料金は予約サイトでご確認ください。</p>
    <?php elseif($page->flight->message() !== ''): ?><div class="flight-offers-message"><?= SearchView::escape($page->flight->message()) ?></div><?php endif ?>
    <section class="booking-sites">
        <h3><?= $page->flight->isDomesticFlight ? '国内向け' : '海外向け' ?>予約サイト</h3><p>出発地と目的地から自動判定しています。</p>
        <div class="booking-site-links">
            <a href="<?= SearchView::escape($page->flight->result['expedia']) ?>" target="_blank" rel="sponsored noopener">Expedia</a>
            <a href="<?= SearchView::escape($page->flight->result['agoda']) ?>" target="_blank" rel="sponsored noopener">Agoda</a>
            <?php if(!$page->flight->isDomesticFlight): ?>
            <a href="<?= SearchView::escape($page->flight->result['skygate']) ?>" target="_blank" rel="sponsored noopener">エアトリ（海外航空券）</a>
            <?php endif ?>
            <?php if($page->flight->isDomesticFlight): ?>
            <a href="<?= SearchView::escape($page->flight->result['airtrip']) ?>" target="_blank" rel="sponsored noopener">エアトリ</a>
            <a href="<?= SearchView::escape($page->flight->result['travelist']) ?>" target="_blank" rel="sponsored noopener">トラベリスト</a>
            <a href="<?= SearchView::escape($page->flight->result['realticket']) ?>" target="_blank" rel="sponsored noopener">リアルチケット</a>
            <?php else: ?>
            <a href="<?= SearchView::escape($page->flight->result['jtb']) ?>" target="_blank" rel="sponsored noopener">JTB</a>
            <a href="<?= SearchView::escape($page->flight->result['skyticket']) ?>" target="_blank" rel="sponsored noopener">SkyTicket</a>
            <?php endif ?>
        </div>
    </section>
    <div class="actions"><a href="<?= SearchView::escape($page->flight->result['maps']) ?>" target="_blank" rel="noopener">Google Maps</a></div>
    <em>予約・決済は各提携先サイトで行われます。</em>
</section>
<?php endif ?>
