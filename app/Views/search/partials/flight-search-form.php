<?php
use App\Views\SearchView;
/** @var \App\ViewModels\SearchPageViewModel $page */
?>
<?php if ($page->flight->errors): ?>
<div class="errors"><b>入力内容をご確認ください</b><ul><?php foreach ($page->flight->errors as $error): ?><li><?= SearchView::escape($error) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
<form method="post" class="flight-search-form">
    <input type="hidden" name="csrf" value="<?= SearchView::escape($page->csrfToken) ?>">
    <input type="hidden" name="search_type" value="flight">
    <fieldset class="trip-type"><legend>旅行タイプ</legend><label><input type="radio" name="trip_type" value="roundtrip" <?= $page->flight->values['return_date'] !== '' || !$page->isSearchResult ? 'checked' : '' ?>> 往復</label><label><input type="radio" name="trip_type" value="oneway" <?= $page->flight->values['return_date'] === '' && $page->isSearchResult && $page->activeTab === 'flight' ? 'checked' : '' ?>> 片道</label></fieldset>
    <label>出発地<input name="origin" value="<?= SearchView::escape($page->flight->values['origin']) ?>" placeholder="例：東京（TYO）" required></label>
    <label>目的地<input name="destination" value="<?= SearchView::escape($page->flight->values['destination']) ?>" placeholder="例：札幌、ソウル（SEL）" required></label>
    <label>出発日<input type="date" name="departure_date" value="<?= SearchView::escape($page->flight->values['departure_date']) ?>" required></label>
    <label>帰着日<input type="date" name="return_date" value="<?= SearchView::escape($page->flight->values['return_date']) ?>"></label>
    <label>人数<select name="travelers"><?php for ($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= (int)$page->flight->values['travelers']===$i?'selected':'' ?>><?= $i ?></option><?php endfor ?></select></label>
    <button>航空券を検索</button>
</form>
