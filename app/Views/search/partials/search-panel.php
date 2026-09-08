<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<section class="panel">
<div class="search-tabs" role="tablist" aria-label="検索タイプ">
<button type="button" class="search-tab<?= $page->activeTab === 'flight' ? ' is-active' : '' ?>" id="flight-tab" role="tab" aria-selected="<?= $page->activeTab === 'flight' ? 'true' : 'false' ?>" aria-controls="flight-panel" data-tab="flight-panel"<?= $page->activeTab !== 'flight' ? ' tabindex="-1"' : '' ?>>✈ 航空券</button>
<button type="button" class="search-tab<?= $page->activeTab === 'hotel' ? ' is-active' : '' ?>" id="hotel-tab" role="tab" aria-selected="<?= $page->activeTab === 'hotel' ? 'true' : 'false' ?>" aria-controls="hotel-panel" data-tab="hotel-panel"<?= $page->activeTab !== 'hotel' ? ' tabindex="-1"' : '' ?>>▣ ホテル</button>
<button type="button" class="search-tab<?= $page->activeTab === 'ferry' ? ' is-active' : '' ?>" id="ferry-tab" role="tab" aria-selected="<?= $page->activeTab === 'ferry' ? 'true' : 'false' ?>" aria-controls="ferry-panel" data-tab="ferry-panel"<?= $page->activeTab !== 'ferry' ? ' tabindex="-1"' : '' ?>>⛴ フェリー</button>
</div>
<div class="tab-panel" id="flight-panel" role="tabpanel" aria-labelledby="flight-tab"<?= $page->activeTab !== 'flight' ? ' hidden' : '' ?>>
<?php echo SearchView::render($page, 'partials/flight-search-form'); ?>
</div>
<div class="tab-panel" id="hotel-panel" role="tabpanel" aria-labelledby="hotel-tab"<?= $page->activeTab !== 'hotel' ? ' hidden' : '' ?>>
<div class="provider-tabs hotel-provider-tabs" role="tablist" aria-label="ホテル検索範囲">
<?php foreach(['domestic' => '国内ホテル', 'korea' => '韓国ホテル', 'overseas' => 'その他海外ホテル'] as $scope => $label): ?>
<button type="button" class="provider-tab hotel-provider-tab<?= $page->hotel->activeHotelScope === $scope ? ' is-active' : '' ?>" id="<?= $scope ?>-hotel-tab" role="tab" aria-selected="<?= $page->hotel->activeHotelScope === $scope ? 'true' : 'false' ?>" aria-controls="<?= $scope ?>-hotel-panel" data-provider-panel="<?= $scope ?>-hotel-panel" data-hotel-scope="<?= $scope ?>"<?= $page->hotel->activeHotelScope !== $scope ? ' tabindex="-1"' : '' ?>><?= $label ?></button>
<?php endforeach ?>
</div>
<?php if($page->hotel->hotelErrors): ?><div class="errors"><b>入力内容をご確認ください</b><ul><?php foreach($page->hotel->hotelErrors as $error): ?><li><?= SearchView::escape($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?php foreach(['domestic' => '国内の目的地（例：東京、大阪）', 'korea' => '韓国の目的地（例：ソウル、水原、釜山）', 'overseas' => '海外の目的地（例：台北、バンコク、Dallas）'] as $scope => $placeholder): ?>
<div class="hotel-provider-panel" id="<?= $scope ?>-hotel-panel" role="tabpanel" aria-labelledby="<?= $scope ?>-hotel-tab"<?= $page->hotel->activeHotelScope !== $scope ? ' hidden' : '' ?>><form method="post" class="hotel-search-form hotel-search-form-shared"><input type="hidden" name="csrf" value="<?= SearchView::escape($page->csrfToken) ?>"><input type="hidden" name="hotel_scope" value="<?= $scope ?>"><input type="hidden" name="hotel_place_id"><input type="hidden" name="hotel_place_address"><input type="hidden" name="hotel_place_latitude"><input type="hidden" name="hotel_place_longitude"><input type="hidden" name="hotel_place_country"><div class="hotel-destination-field"><label for="<?= $scope ?>-hotel-destination">目的地</label><span class="hotel-destination-control"><input id="<?= $scope ?>-hotel-destination" name="hotel_destination" value="<?= SearchView::escape($page->hotel->hotelValues['hotel_destination']) ?>" placeholder="<?= SearchView::escape($placeholder) ?>" autocomplete="off" aria-autocomplete="list" aria-expanded="false" required><span class="hotel-place-loading" aria-hidden="true" hidden></span></span><div class="hotel-place-suggestions" role="listbox" aria-label="目的地候補" hidden></div><p class="hotel-place-status" aria-live="polite"></p></div><label>チェックイン<input type="date" name="check_in_date" value="<?= SearchView::escape($page->hotel->hotelValues['check_in_date']) ?>" required></label><label>チェックアウト<input type="date" name="check_out_date" value="<?= SearchView::escape($page->hotel->hotelValues['check_out_date']) ?>" required></label><label>大人<select name="hotel_adults"><?php for($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= (int)$page->hotel->hotelValues['hotel_adults']===$i?'selected':'' ?>><?= $i ?>名</option><?php endfor ?></select></label><label>子供<select name="hotel_children"><?php for($i=0;$i<=9;$i++): ?><option value="<?= $i ?>" <?= (int)$page->hotel->hotelValues['hotel_children']===$i?'selected':'' ?>><?= $i ?>名</option><?php endfor ?></select></label><div class="hotel-search-actions"><button name="search_type" value="hotel">ホテルを検索</button></div></form></div>
<?php endforeach ?>
</div>
<div class="tab-panel" id="ferry-panel" role="tabpanel" aria-labelledby="ferry-tab"<?= $page->activeTab !== 'ferry' ? ' hidden' : '' ?>>
<?php echo SearchView::render($page, 'partials/ferry-search-form'); ?>
</div>
</section>
