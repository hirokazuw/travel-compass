<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<?php if($page->activeTab === 'hotel' && !$page->hotel->hotelErrors): ?>
<section class="result overseas-hotel-result" data-provider-results="apify" data-hotel-result-scope="<?= SearchView::escape($page->hotel->activeHotelScope) ?>">
    <small>YOUR PLAN</small>
    <h2><?= SearchView::escape($page->hotel->hotelValues['hotel_destination']) ?>のホテル</h2>
    <p><?= SearchView::escape($page->hotel->hotelValues['check_in_date']) ?> 〜 <?= SearchView::escape($page->hotel->hotelValues['check_out_date']) ?>・大人<?= SearchView::escape($page->hotel->hotelValues['hotel_adults']) ?>名・子供<?= SearchView::escape($page->hotel->hotelValues['hotel_children']) ?>名</p>
    <?php if($page->hotel->hotels): ?>
    <?php if($page->hotel->hotelOtaGuide['show_guide'] ?? false): ?><aside class="country-hotel-ota"><p><?= SearchView::escape($page->hotel->hotelOtaGuide['message']) ?></p><a href="<?= SearchView::escape($page->hotel->hotelOtaGuide['url']) ?>" target="_blank" rel="sponsored noopener"><?= SearchView::escape($page->hotel->hotelOtaGuide['label']) ?></a></aside><?php endif ?>
    <div class="overseas-hotel-cards" aria-label="ホテル検索結果">
        <?php foreach($page->hotel->hotels as $hotelIndex => $hotel): ?>
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
            $bookingLinks = (array)($hotel['booking_links'] ?? []);
        ?>
        <article class="overseas-hotel-card"<?= $hotelIndex >= 6 ? ' hidden data-extra-overseas-hotel' : '' ?>>
            <div class="overseas-hotel-image-wrap"><?php if($image !== ''): ?><img src="<?= SearchView::escape($image) ?>" alt="<?= SearchView::escape($hotel['name']) ?>" loading="lazy" data-hotel-image data-fallback-images="<?= SearchView::escape(json_encode($fallbackImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"><div class="overseas-hotel-placeholder" hidden>NO IMAGE</div><?php else: ?><div class="overseas-hotel-placeholder">NO IMAGE</div><?php endif ?></div>
            <div class="overseas-hotel-body">
                <h3><?php if($officialUrl !== ''): ?><a class="hotel-official-link" href="<?= SearchView::escape($officialUrl) ?>" target="_blank" rel="noopener noreferrer"><?= SearchView::escape($hotel['name']) ?></a><?php else: ?><?= SearchView::escape($hotel['name']) ?><?php endif ?></h3>
                <?php if($filledStars > 0 || $rating !== null): ?><p class="overseas-hotel-rating"><?php if($filledStars > 0): ?><span aria-label="5つ星中<?= $filledStars ?>"><?= str_repeat('★', $filledStars) ?><?= str_repeat('☆', 5 - $filledStars) ?></span><?php endif ?> <?php if($rating !== null): ?><b><?= SearchView::escape(number_format((float)$rating, 1)) ?></b><?php if($hotel['reviews'] !== null): ?>（<?= number_format((int)$hotel['reviews']) ?>件）<?php endif ?><?php endif ?></p><?php endif ?>
                <?php if($hotel['description']): ?><p class="overseas-hotel-description"><?= SearchView::escape(mb_strimwidth((string)$hotel['description'], 0, 110, '…')) ?></p><?php endif ?>
                <?php if(($hotel['address'] ?? '') !== ''): ?><p class="hotel-address">📍 <?= SearchView::escape($hotel['address']) ?></p><?php endif ?>
                <?php if($hotel['amenities']): ?><ul class="overseas-hotel-amenities"><?php foreach(array_slice((array)$hotel['amenities'], 0, 4) as $amenity): ?><li><?= SearchView::escape($amenity) ?></li><?php endforeach ?></ul><?php endif ?>
                <dl class="overseas-hotel-times"><?php if($hotel['check_in_time']): ?><div><dt>チェックイン</dt><dd><?= SearchView::escape($hotel['check_in_time']) ?></dd></div><?php endif ?><?php if($hotel['check_out_time']): ?><div><dt>チェックアウト</dt><dd><?= SearchView::escape($hotel['check_out_time']) ?></dd></div><?php endif ?></dl>
            </div>
            <div class="overseas-hotel-price">
                <?php if($nightlyRate > 0): ?><strong>￥<?= number_format($nightlyRate) ?>〜 <span>/ 1泊</span></strong><?php else: ?><small>1泊料金は予約サイトでご確認ください</small><?php endif ?>
                <?php if($stayTotal > 0): ?><small>宿泊合計 ￥<?= number_format($stayTotal) ?></small><?php endif ?>
                <?php if($officialUrl !== ''): ?><a class="hotel-official-button" href="<?= SearchView::escape($officialUrl) ?>" target="_blank" rel="noopener noreferrer">詳細を見る</a><?php endif ?>
                <?php if(isset($page->hotel->rakutenHotelLinks[$hotelIndex])): ?><a class="hotel-rakuten-button" href="<?= SearchView::escape($page->hotel->rakutenHotelLinks[$hotelIndex]) ?>" target="_blank" rel="sponsored noopener">楽天トラベルで予約</a><?php endif ?>
                <?php if(($bookingLinks['jalan'] ?? '') !== ''): ?><a class="hotel-booking-button is-jalan" href="<?= SearchView::escape($bookingLinks['jalan']) ?>" target="_blank" rel="sponsored noopener">じゃらん</a><?php endif ?>
                <?php if(($bookingLinks['yahoo'] ?? '') !== ''): ?><a class="hotel-booking-button is-yahoo" href="<?= SearchView::escape($bookingLinks['yahoo']) ?>" target="_blank" rel="sponsored noopener">Yahoo!トラベル</a><?php endif ?>
                <?php if(($bookingLinks['ikyu'] ?? '') !== ''): ?><a class="hotel-booking-button is-ikyu" href="<?= SearchView::escape($bookingLinks['ikyu']) ?>" target="_blank" rel="sponsored noopener">一休.com</a><?php endif ?>
                <?php if(!in_array('expedia', (array)($page->hotel->hotelOtaGuide['hidden_booking_links'] ?? []), true) && ($bookingLinks['expedia'] ?? '') !== ''): ?><a class="hotel-booking-button is-expedia" href="<?= SearchView::escape($bookingLinks['expedia']) ?>" target="_blank" rel="sponsored noopener">Expedia</a><?php endif ?>
                <?php if(!in_array('hotels', (array)($page->hotel->hotelOtaGuide['hidden_booking_links'] ?? []), true) && ($bookingLinks['hotels'] ?? '') !== ''): ?><a class="hotel-booking-button is-hotels" href="<?= SearchView::escape($bookingLinks['hotels']) ?>" target="_blank" rel="sponsored noopener">Hotels.com</a><?php endif ?>
            </div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if(count($page->hotel->hotels) > 6): ?><button type="button" class="overseas-hotels-toggle" aria-expanded="false">もっと見る</button><?php endif ?>
    <p class="price-note">表示価格はGoogle Hotelsの検索結果による参考価格です。実際の料金は予約サイトでご確認ください。</p>
    <?php elseif($page->hotel->message() !== ''): ?><div class="flight-offers-message"><?= SearchView::escape($page->hotel->message()) ?></div><?php endif ?>
</section>
<?php endif ?>
