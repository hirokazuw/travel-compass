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
    <?php foreach (['origin' => '出発地', 'destination' => '目的地'] as $field => $label): ?>
    <div class="flight-city-field" data-flight-city>
        <label for="flight-<?= $field ?>"><?= $label ?><input id="flight-<?= $field ?>" name="<?= $field ?>" value="<?= SearchView::escape($page->flight->cityLabels[$field] ?? $page->flight->values[$field]) ?>" placeholder="都市名・IATAコード" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="flight-<?= $field ?>-suggestions" required></label>
        <input type="hidden" name="<?= $field ?>_iata" value="<?= isset($page->flight->cityLabels[$field]) ? SearchView::escape($page->flight->values[$field]) : '' ?>">
        <div class="flight-city-suggestions" id="flight-<?= $field ?>-suggestions" role="listbox" aria-label="<?= $label ?>の候補" hidden></div>
        <p class="flight-city-status" aria-live="polite"></p>
    </div>
    <?php endforeach ?>
    <label>出発日<input type="date" name="departure_date" value="<?= SearchView::escape($page->flight->values['departure_date']) ?>" required></label>
    <label>帰着日<input type="date" name="return_date" value="<?= SearchView::escape($page->flight->values['return_date']) ?>"></label>
    <label>人数<select name="travelers"><?php for ($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= (int)$page->flight->values['travelers']===$i?'selected':'' ?>><?= $i ?></option><?php endfor ?></select></label>
    <button>航空券を検索</button>
</form>
