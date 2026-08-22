<?php if($ferryErrors): ?><div class="errors"><b>入力内容をご確認ください</b><ul><?php foreach($ferryErrors as $error): ?><li><?= $h($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<div class="ferry-search-modes" role="tablist" aria-label="フェリーの探し方">
    <button type="button" class="ferry-search-mode<?= $ferryValues['ferry_search_mode'] !== 'map' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $ferryValues['ferry_search_mode'] !== 'map' ? 'true' : 'false' ?>" data-ferry-mode="conditions">条件から探す</button>
    <button type="button" class="ferry-search-mode<?= $ferryValues['ferry_search_mode'] === 'map' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $ferryValues['ferry_search_mode'] === 'map' ? 'true' : 'false' ?>" data-ferry-mode="map">地図から探す</button>
</div>
<div class="ferry-search-mode-panel" data-ferry-mode-panel="conditions"<?= $ferryValues['ferry_search_mode'] === 'map' ? ' hidden' : '' ?>>
    <form method="post" class="ferry-search-form">
        <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>"><input type="hidden" name="ferry_search_mode" value="conditions">
        <div class="ferry-company-field"><label for="ferry-company-name">フェリー会社</label><input type="hidden" name="ferry_company_id" value="<?= $h($ferryValues['ferry_company_id']) ?>"><input id="ferry-company-name" name="ferry_company_name" value="<?= $h($ferryValues['ferry_company_name']) ?>" placeholder="会社名を入力" maxlength="150" autocomplete="off" aria-autocomplete="list" aria-expanded="false" required><div class="ferry-company-suggestions" role="listbox" aria-label="フェリー会社候補" hidden></div><p class="ferry-company-status" aria-live="polite"></p></div>
        <label>航路<select name="ferry_route_id"<?= $ferryValues['ferry_company_id'] === '' || $ferryRouteOptions === [] ? ' disabled' : '' ?> required><option value=""><?= $ferryValues['ferry_company_id'] === '' ? '先にフェリー会社を選択してください' : ($ferryRouteOptions === [] ? '利用可能な航路がありません' : '航路を選択してください') ?></option><?php foreach($ferryRouteOptions as $option): ?><option value="<?= $h($option['id']) ?>"<?= (string)$option['id'] === $ferryValues['ferry_route_id'] ? ' selected' : '' ?>><?= $h($option['label']) ?></option><?php endforeach ?></select></label>
        <button name="search_type" value="ferry">フェリーを検索</button>
    </form>
</div>
<div class="ferry-search-mode-panel ferry-map-search" data-ferry-mode-panel="map" data-ferry-map data-csrf="<?= $h($_SESSION['csrf']) ?>"<?= $ferryValues['ferry_search_mode'] !== 'map' ? ' hidden' : '' ?>>
    <div class="ferry-map-heading"><div><small>MAP SEARCH</small><h3>地域を選択してください</h3><p data-ferry-map-status>地域 → A地点 → B地点の順に選択します。</p></div></div>
    <div class="ferry-map-layout">
        <div class="ferry-map-canvas" aria-label="フェリー航路検索用の簡易日本地図">
            <img class="ferry-map-land" src="public/assets/ferry-map-japan.png" alt="日本列島の地図" draggable="false">
            <svg class="ferry-map-route-lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"></svg>
            <div class="ferry-map-regions" data-ferry-map-regions>
                <?php foreach(['hokkaido'=>['北海道',70,21],'tohoku'=>['東北',65,42],'kanto'=>['関東',60,65],'hokuriku'=>['北陸・新潟',51,56],'tokai'=>['東海',51,68],'kinki'=>['近畿',41,71],'chugoku'=>['中国',29,69],'shikoku'=>['四国',33,76],'kyushu'=>['九州',20,82],'okinawa'=>['沖縄',78,85],'overseas'=>['海外',8,23]] as $regionId=>$region): ?><button type="button" class="ferry-region" data-region="<?= $h($regionId) ?>" style="--map-x:<?= $region[1] ?>%;--map-y:<?= $region[2] ?>%"><?= $h($region[0]) ?></button><?php endforeach ?>
            </div>
            <div class="ferry-map-pins" data-ferry-map-pins></div>
            <aside class="ferry-map-routes" data-ferry-map-routes hidden><h4>選択した航路</h4><div data-ferry-map-route-list></div></aside>
        </div>
        <aside class="ferry-map-selection" data-ferry-map-selection hidden><h4 data-ferry-map-selection-title>A地点を選択</h4><div data-ferry-map-selection-list></div></aside>
    </div>
    <form method="post" class="ferry-map-route-form" hidden><input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>"><input type="hidden" name="search_type" value="ferry"><input type="hidden" name="ferry_search_mode" value="map"><input type="hidden" name="ferry_company_name"><input type="hidden" name="ferry_company_id"><input type="hidden" name="ferry_route_id"><input type="hidden" name="ferry_route_label"></form>
</div>
