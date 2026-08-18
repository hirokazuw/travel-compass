<?php if($activeTab==='hotel'&&in_array($activeHotelProvider,['apify','scrapedo'],true)&&!$hotelErrors): ?>
<section class="result overseas-hotel-result" data-provider-results="<?= $h($activeHotelProvider) ?>">
    <small>YOUR PLAN</small>
    <h2><?= $h($hotelValues['hotel_destination']) ?>のホテル</h2>
    <p><?= $h($hotelValues['check_in_date']) ?> 〜 <?= $h($hotelValues['check_out_date']) ?>・大人<?= $h($hotelValues['hotel_adults']) ?>名・子供<?= $h($hotelValues['hotel_children']) ?>名</p>
    <?php if($overseasHotels): ?>
    <?php $nightCount=max(1,(new DateTimeImmutable($hotelValues['check_in_date']))->diff(new DateTimeImmutable($hotelValues['check_out_date']))->days); ?>
    <h3 class="flight-result-subheading">参考価格</h3>
    <div class="overseas-hotel-cards" aria-label="海外ホテル参考価格">
        <?php foreach($overseasHotels as $hotelIndex=>$hotel): ?>
        <?php $hotelImages=(array)($hotel['images']??[]); $image=(string)($hotelImages[0]??''); $fallbackImages=array_slice($hotelImages,1); $rating=(float)($hotel['overall_rating']??0); $hotelClass=(int)preg_replace('/[^0-9]/','',(string)($hotel['hotel_class']??'')); $filledStars=max(0,min(5,$hotelClass)); $nightlyRate=(int)$hotel['price']>0?(int)$hotel['price']:((int)$hotel['total_rate']>0?(int)round((int)$hotel['total_rate']/$nightCount):0); $stayTotal=(int)$hotel['total_rate']>0?(int)$hotel['total_rate']:($nightlyRate*$nightCount); ?>
        <article class="overseas-hotel-card"<?= $hotelIndex>=6?' hidden data-extra-overseas-hotel':'' ?>>
            <div class="overseas-hotel-image-wrap"><?php if($image!==''): ?><img src="<?= $h($image) ?>" alt="<?= $h($hotel['name']) ?>" loading="lazy" data-hotel-image data-fallback-images="<?= $h(json_encode($fallbackImages,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)) ?>"><div class="overseas-hotel-placeholder" hidden>NO IMAGE</div><?php else: ?><div class="overseas-hotel-placeholder">NO IMAGE</div><?php endif ?></div>
            <div class="overseas-hotel-body">
                <h3><?= $h($hotel['name']) ?></h3>
                <?php if($filledStars>0||$rating>0): ?><p class="overseas-hotel-rating"><?php if($filledStars>0): ?><span aria-label="5つ星中<?= $filledStars ?>"><?= str_repeat('★',$filledStars) ?><?= str_repeat('☆',5-$filledStars) ?></span><?php endif ?> <?php if($rating>0): ?><b><?= $h(number_format($rating,1)) ?></b><?php if($hotel['reviews']>0): ?>（<?= number_format($hotel['reviews']) ?>件）<?php endif ?><?php endif ?></p><?php endif ?>
                <?php if($hotel['description']!==''): ?><p class="overseas-hotel-description"><?= $h(mb_strimwidth($hotel['description'],0,110,'…')) ?></p><?php endif ?>
                <?php if(!empty($hotel['amenities'])): ?><ul class="overseas-hotel-amenities"><?php foreach(array_slice((array)$hotel['amenities'],0,4) as $amenity): ?><li><?= $h($amenity) ?></li><?php endforeach ?></ul><?php endif ?>
                <dl class="overseas-hotel-times"><?php if($hotel['check_in_time']!==''): ?><div><dt>チェックイン</dt><dd><?= $h($hotel['check_in_time']) ?></dd></div><?php endif ?><?php if($hotel['check_out_time']!==''): ?><div><dt>チェックアウト</dt><dd><?= $h($hotel['check_out_time']) ?></dd></div><?php endif ?></dl>
            </div>
            <div class="overseas-hotel-price"><?php if($nightlyRate>0): ?><strong>￥<?= number_format($nightlyRate) ?>〜 <span>/ 1泊</span></strong><small>合計 ￥<?= number_format($stayTotal) ?></small><?php else: ?><small>料金情報を取得できませんでした</small><?php endif ?></div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if(count($overseasHotels)>6): ?><button type="button" class="overseas-hotels-toggle" aria-expanded="false">もっと見る</button><?php endif ?>
    <p class="price-note">表示価格はGoogle Hotelsの検索結果による参考価格です。実際の料金は予約サイトでご確認ください。</p>
    <section class="booking-sites"><h3>予約サイト</h3><p>同じ条件を各予約サイトで確認できます。</p><div class="booking-site-links"><?php foreach(['expedia'=>'Expedia','hotels'=>'Hotels.com','jtb'=>'JTB'] as $siteKey=>$siteName): ?><?php if(isset($overseasBookingLinks[$siteKey])): ?><a href="<?= $h($overseasBookingLinks[$siteKey]) ?>" target="_blank" rel="sponsored noopener"><?= $h($siteName) ?></a><?php endif ?><?php endforeach ?></div></section>
    <em>予約・決済は各提携先サイトで行われます。</em>
    <?php elseif($overseasHotelMessage!==''): ?><div class="flight-offers-message"><?= $h($overseasHotelMessage) ?></div><?php endif ?>
</section>
<?php endif ?>
