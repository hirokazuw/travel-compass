<?php if ($errors): ?>
<div class="errors"><b>入力内容をご確認ください</b><ul><?php foreach ($errors as $error): ?><li><?= $h($error) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
<form method="post" class="flight-search-form">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
    <input type="hidden" name="search_type" value="flight">
    <fieldset class="trip-type"><legend>旅行タイプ</legend><label><input type="radio" name="trip_type" value="roundtrip" <?= $values['return_date'] !== '' || $_SERVER['REQUEST_METHOD'] !== 'POST' ? 'checked' : '' ?>> 往復</label><label><input type="radio" name="trip_type" value="oneway" <?= $values['return_date'] === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && $activeTab === 'flight' ? 'checked' : '' ?>> 片道</label></fieldset>
    <label>出発地<input name="origin" value="<?= $h($values['origin']) ?>" placeholder="例：東京（TYO）" required></label>
    <label>目的地<input name="destination" value="<?= $h($values['destination']) ?>" placeholder="例：札幌、ソウル（SEL）" required></label>
    <label>出発日<input type="date" name="departure_date" value="<?= $h($values['departure_date']) ?>" required></label>
    <label>帰着日<input type="date" name="return_date" value="<?= $h($values['return_date']) ?>"></label>
    <label>人数<select name="travelers"><?php for ($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= (int)$values['travelers']===$i?'selected':'' ?>><?= $i ?></option><?php endfor ?></select></label>
    <button>航空券を検索</button>
</form>
