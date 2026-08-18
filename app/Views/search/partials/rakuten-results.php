<?php if($activeTab==='hotel'&&$activeHotelProvider==='rakuten'&&!$rakutenErrors): ?>
<section class="result rakuten-result-section" data-provider-results="rakuten">
    <small>RAKUTEN TRAVEL RESULTS</small>
    <h2><?= $h($rakutenValues['rakuten_destination']) ?>のホテル</h2>
    <p class="rakuten-result-conditions"><?= $h($rakutenValues['rakuten_check_in']) ?> 〜 <?= $h($rakutenValues['rakuten_check_out']) ?>・大人<?= $h($rakutenValues['rakuten_adults']) ?>名・子供<?= $h($rakutenValues['rakuten_children']) ?>名</p>
    <?php if($rakutenHotels): ?>
    <div class="rakuten-hotel-results">
        <?php foreach($rakutenHotels as $rakutenIndex=>$rakutenHotel): ?>
        <?php $otaLinks=(array)($rakutenHotel['booking_links']??[]); ?>
        <article class="rakuten-hotel-card"<?= $rakutenIndex>=5?' hidden data-extra-rakuten-hotel':'' ?>>
            <div class="rakuten-hotel-photo-wrap"><?php if($rakutenHotel['image']!==''): ?><img class="rakuten-hotel-photo" src="<?= $h($rakutenHotel['image']) ?>" alt="<?= $h($rakutenHotel['name']) ?>" loading="lazy"><?php else: ?><div class="rakuten-hotel-photo rakuten-hotel-photo-placeholder">No image</div><?php endif ?></div>
            <div class="rakuten-hotel-main">
                <h3><?= $h($rakutenHotel['name']) ?></h3>
                <?php if($rakutenHotel['rating']!==''): ?><p class="rakuten-rating"><b><?= $h($rakutenHotel['rating']) ?></b> / 5<?php if($rakutenHotel['reviews']>0): ?> <span>（<?= number_format($rakutenHotel['reviews']) ?>件）</span><?php endif ?></p><?php endif ?>
                <?php if($rakutenHotel['address']!==''): ?><p>📍 <?= $h($rakutenHotel['address']) ?></p><?php endif ?>
                <?php if($rakutenHotel['access']!==''): ?><p><?= $h($rakutenHotel['access']) ?></p><?php endif ?>
                <?php if($rakutenHotel['description']!==''): ?><p class="rakuten-description"><?= $h(mb_strimwidth($rakutenHotel['description'],0,130,'…')) ?></p><?php endif ?>
                <?php if($rakutenHotel['facilities']): ?><p class="rakuten-facilities"><?= $h(implode(' / ',$rakutenHotel['facilities'])) ?></p><?php endif ?>
            </div>
            <aside class="rakuten-hotel-price">
                <?php if($rakutenHotel['price']>0): ?><small>1室1泊あたり参考</small><strong>¥<?= number_format($rakutenHotel['price']) ?>〜</strong><small>※楽天トラベル掲載価格</small><?php else: ?><p>料金は楽天トラベルでご確認ください</p><?php endif ?>
                <div class="rakuten-booking-sites">
                    <b>予約サイト</b>
                    <a class="rakuten-booking-site is-rakuten" href="<?= $h($rakutenHotel['url']) ?>" target="_blank" rel="sponsored noopener"><span>楽天トラベル</span><?php if($rakutenHotel['price']>0): ?><small>¥<?= number_format($rakutenHotel['price']) ?>〜</small><?php endif ?></a>
                    <?php if(isset($otaLinks['jalan'])): ?><a class="rakuten-booking-site is-jalan" href="<?= $h($otaLinks['jalan']) ?>" target="_blank" rel="sponsored noopener"><span>じゃらん</span><small>このホテルを検索</small></a><?php endif ?>
                    <?php if(isset($otaLinks['yahoo'])): ?><a class="rakuten-booking-site is-yahoo" href="<?= $h($otaLinks['yahoo']) ?>" target="_blank" rel="sponsored noopener"><span>Yahoo!トラベル</span><small>このホテルを検索</small></a><?php endif ?>
                    <?php if(isset($otaLinks['ikyu'])): ?><a class="rakuten-booking-site is-ikyu" href="<?= $h($otaLinks['ikyu']) ?>" target="_blank" rel="sponsored noopener"><span>一休.com</span><small>このホテルを検索</small></a><?php endif ?>
                    <?php if(isset($otaLinks['expedia'])): ?><a class="rakuten-booking-site is-expedia" href="<?= $h($otaLinks['expedia']) ?>" target="_blank" rel="sponsored noopener"><span>Expedia</span><small>このホテルを検索</small></a><?php endif ?>
                </div>
            </aside>
        </article>
        <?php endforeach ?>
    </div>
    <?php if(count($rakutenHotels)>5): ?><button type="button" class="rakuten-results-toggle" data-step="5">もっと見る</button><?php endif ?>
    <p class="price-note">参考価格は楽天トラベル掲載情報です。料金・空室状況・プラン内容は各予約サイトでご確認ください。</p>
    <?php elseif($rakutenMessage !== ''): ?><div class="flight-offers-message"><?= $h($rakutenMessage) ?></div><?php endif ?>
</section>
<?php endif ?>
