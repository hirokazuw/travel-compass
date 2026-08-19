<?php if($activeTab === 'hotel' && !$hotelErrors): ?>
<section class="result overseas-hotel-result" data-provider-results="apify" data-hotel-result-scope="<?= $h($activeHotelScope) ?>">
    <small>YOUR PLAN</small>
    <h2><?= $h($hotelValues['hotel_destination']) ?>のホテル</h2>
    <p><?= $h($hotelValues['check_in_date']) ?> 〜 <?= $h($hotelValues['check_out_date']) ?>・大人<?= $h($hotelValues['hotel_adults']) ?>名・子供<?= $h($hotelValues['hotel_children']) ?>名</p>
    <?php if($hotels): ?>
    <div class="overseas-hotel-cards" aria-label="ホテル検索結果">
        <?php foreach($hotels as $hotelIndex => $hotel): ?>
        <?php
            $images = (array)($hotel['image_urls'] ?? []);
            $image = (string)($images[0] ?? '');
            $fallbackImages = array_slice($images, 1);
            $rating = $hotel['rating'] ?? null;
            $hotelClass = (int)preg_replace('/[^0-9]/', '', (string)($hotel['hotel_class'] ?? ''));
            $filledStars = max(0, min(5, $hotelClass));
            $nightlyRate = (int)($hotel['price_per_night'] ?? 0);
            $stayTotal = (int)($hotel['total_price'] ?? 0);
            $officialUrl = (string)($hotel['official_url'] ?? '');
        ?>
        <article class="overseas-hotel-card"<?= $hotelIndex >= 6 ? ' hidden data-extra-overseas-hotel' : '' ?>>
            <div class="overseas-hotel-image-wrap"><?php if($image !== ''): ?><img src="<?= $h($image) ?>" alt="<?= $h($hotel['name']) ?>" loading="lazy" data-hotel-image data-fallback-images="<?= $h(json_encode($fallbackImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"><div class="overseas-hotel-placeholder" hidden>NO IMAGE</div><?php else: ?><div class="overseas-hotel-placeholder">NO IMAGE</div><?php endif ?></div>
            <div class="overseas-hotel-body">
                <h3><?php if($officialUrl !== ''): ?><a class="hotel-official-link" href="<?= $h($officialUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $h($hotel['name']) ?></a><?php else: ?><?= $h($hotel['name']) ?><?php endif ?></h3>
                <?php if($filledStars > 0 || $rating !== null): ?><p class="overseas-hotel-rating"><?php if($filledStars > 0): ?><span aria-label="5つ星中<?= $filledStars ?>"><?= str_repeat('★', $filledStars) ?><?= str_repeat('☆', 5 - $filledStars) ?></span><?php endif ?> <?php if($rating !== null): ?><b><?= $h(number_format((float)$rating, 1)) ?></b><?php if($hotel['reviews'] !== null): ?>（<?= number_format((int)$hotel['reviews']) ?>件）<?php endif ?><?php endif ?></p><?php endif ?>
                <?php if($hotel['description']): ?><p class="overseas-hotel-description"><?= $h(mb_strimwidth((string)$hotel['description'], 0, 110, '…')) ?></p><?php endif ?>
                <?php if(($hotel['address'] ?? '') !== ''): ?><p class="hotel-address">📍 <?= $h($hotel['address']) ?></p><?php endif ?>
                <?php if($hotel['amenities']): ?><ul class="overseas-hotel-amenities"><?php foreach(array_slice((array)$hotel['amenities'], 0, 4) as $amenity): ?><li><?= $h($amenity) ?></li><?php endforeach ?></ul><?php endif ?>
                <dl class="overseas-hotel-times"><?php if($hotel['check_in_time']): ?><div><dt>チェックイン</dt><dd><?= $h($hotel['check_in_time']) ?></dd></div><?php endif ?><?php if($hotel['check_out_time']): ?><div><dt>チェックアウト</dt><dd><?= $h($hotel['check_out_time']) ?></dd></div><?php endif ?></dl>
            </div>
            <div class="overseas-hotel-price">
                <?php if($nightlyRate > 0): ?><strong>￥<?= number_format($nightlyRate) ?>〜 <span>/ 1泊</span></strong><?php else: ?><small>1泊料金は予約サイトでご確認ください</small><?php endif ?>
                <?php if($stayTotal > 0): ?><small>宿泊合計 ￥<?= number_format($stayTotal) ?></small><?php endif ?>
                <?php if($officialUrl !== ''): ?><a class="hotel-official-button" href="<?= $h($officialUrl) ?>" target="_blank" rel="noopener noreferrer">公式サイト</a><?php endif ?>
                <?php if(isset($rakutenHotelLinks[$hotelIndex])): ?><a class="hotel-rakuten-button" href="<?= $h($rakutenHotelLinks[$hotelIndex]) ?>" target="_blank" rel="sponsored noopener">楽天トラベルで予約</a><?php endif ?>
            </div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if(count($hotels) > 6): ?><button type="button" class="overseas-hotels-toggle" aria-expanded="false">もっと見る</button><?php endif ?>
    <p class="price-note">表示価格はGoogle Hotelsの検索結果による参考価格です。実際の料金は予約サイトでご確認ください。</p>
    <section class="booking-sites"><h3><?= $activeHotelScope === 'domestic' ? '国内向け予約サイト' : '海外向け予約サイト' ?></h3><p>同じ条件を各予約サイトで確認できます。</p><div class="booking-site-links"><?php $siteNames=$activeHotelScope === 'domestic'?['jalan'=>'じゃらん','yahoo'=>'Yahoo!トラベル','ikyu'=>'一休.com','expedia'=>'Expedia']:['expedia'=>'Expedia','hotels'=>'Hotels.com','jtb'=>'JTB']; foreach($siteNames as $siteKey=>$siteName): ?><?php if(isset($hotelBookingLinks[$siteKey])): ?><a href="<?= $h($hotelBookingLinks[$siteKey]) ?>" target="_blank" rel="sponsored noopener"><?= $h($siteName) ?></a><?php endif ?><?php endforeach ?></div></section>
    <?php elseif($hotelMessage !== ''): ?><div class="flight-offers-message"><?= $h($hotelMessage) ?></div><?php endif ?>
</section>
<?php endif ?>
