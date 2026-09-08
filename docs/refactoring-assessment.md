# Travel Compass リファクタリング診断

V1.9.1: P1-1〜P1-7の実装と契約テストを反映済みです。P1-5の航空券・目的地候補の実response fixture補完は残っています。以下の調査基準・総合評価は初回診断時点の記録です。

調査基準: `master` / `35ea3706be05c2e459fe64e0b6790a133f1d02e2`（2026-08-23）

調査方法: PHP全ファイルの構文検査、クラス・メソッドの参照検索、起動時の依存構築、Request／Controller／Service／Model／View／JavaScript／DDL・migration／設定・ドキュメントの静的確認。外部API、ブラウザ、実DBを使う動作確認は行っていません。

## 1. 総合評価

- PHP構文検査は全ファイル成功。CSRF、入力値検証、prepared statement、HTML escape、外部URLのscheme検証など、基本的な安全策は維持されています。
- 前回基準以後にフェリー検索と航空会社マスタ連携が追加され、`FerryController`への分離も行われています。一方、DB初期構築、起動時の依存構築、画面状態、JavaScript、保守文書が新機能に追随していません。
- 最優先はコード分割そのものではなく、自動testです。DB baseline整備、検索本体からの履歴DB障害の分離、環境固有設定のGit管理からの分離は対応済みです。
- 依存追加なしの自動test runnerとGitHub Actionsを追加しました。Requests、Normalizer、URL Builders、IATA、航空会社集約、ホテル名寄せ、フェリー契約、baselineを自動検査します。
- 主な変更集中箇所は `SearchController`（254行）、`FlightUrlBuilder`（388行）、`FlightCity`（281行）、`ApifyResponseNormalizer`（201行）、`public/assets/app.js`（811行）、`public/index.php`（104行）です。行数だけを理由に分割せず、変更理由と障害境界で分けるべきです。

## 2. P0: リリース・再現性・可用性

### P0-1. DB初期構築を現行機能と一致させる（対応済み）

- 対象: `database/schema.sql`, `database/migrations/archive/`, README
- 対応: 現DBの構造dumpを照合し、`schema.sql`へ`airlines`, `ferry_companies`, `ferry_routes`のDDLと現行seedを統合しました。検索履歴・`iata_cities`を含むfresh install用baselineです。履歴の実データは含めていません。
- 運用: 新規環境にはbaselineを一括適用します。既存DBへの現行migration適用は完了しており、統合済みSQLは`database/migrations/archive/`へ保管しています。baselineやarchive内のSQLを既存DBへ再適用しません。
- 先に固定するtest: 空DBへの一括適用、再適用可否、外部キー、主要seed件数、各Modelの代表SELECT。

### P0-2. 履歴DB障害を検索・初期画面から分離する（対応済み）

- 対象: `SearchController::handleFlightSearch()`, `handleHotelSearch()`, `index()`, `SearchHistory`
- 対応: `createFlight()`／`createHotel()`と`recent()`を個別のbest-effort処理にし、例外時は機能別のerror logを残して検索・描画を継続するようにしました。
- 保存時点: 従来どおりValidation成功直後に保存を試みるため、履歴は「検索成功履歴」ではなく「有効な入力履歴」です。
- 障害時: INSERT失敗時も航空券・ホテル検索を継続し、SELECT失敗時は最近の検索を空として画面を描画します。
- test候補: INSERT失敗、SELECT失敗、外部検索成功／失敗との組み合わせ。

### P0-3. 環境固有設定をGit管理から分離する（対応済み）

- 対象: `config/config.php`, `.vscode/sftp.json`, `.gitignore`
- 対応: `config/config.php`と`.vscode/sftp.json`をローカルに残したままGitの追跡対象から外し、`.gitignore`へ追加しました。`config/config.example.php`は引き続き追跡します。
- setup: 新規環境では`config/config.example.php`を`config/config.php`へコピーし、環境固有値を設定します。
- 残作業: 過去のGit履歴は書き換えていません。公開・共有済みの接続情報、秘密鍵、credentialは失効・再発行要否を別途監査します。

### P0-4. 最低限の自動test基盤を作る（対応済み）

- 対象: Requests、`ApifyResponseNormalizer`、`FlightOfferAggregator`、URL Builders、`FlightCity`、フェリーService／Model契約
- 対応: `php tests/run.php`で動く軽量runnerを追加し、外部通信や本番設定なしで17件の契約testを実行できるようにしました。
- 対象: 日付・人数・CSRF、ホテル／目的地／航空券response正規化、価格parse、ホテル／航空券URL、IATA都市圏、航空会社集約、ホテル名寄せ、フェリー会社と航路ID、地図用route変換、baseline構成。
- DB: 通常testはインメモリSQLiteを使用します。GitHub ActionsではMySQL 8の空DBへ`database/schema.sql`を投入し、6テーブル、master件数、外部キー、履歴data非混入を検査します。
- 残候補: Actorの実responseを匿名化したfixtureの追加、Controller／View／JavaScriptのbrowser test、障害系の網羅。

## 3. P1: 変更容易性と障害境界

### P1-1. HTTP Actionと画面組立を分離する（対応済み）

航空券・ホテル・フェリーを検索種別ごとのActionへ移し、ホテル候補検索も専用Actionへ分離しました。旧`FerryController`の検索・JSON処理は`FerrySearchAction`へ移しています。`SearchController`は登録されたHTML／JSON ActionのdispatchとHTML表示前のCSRF更新を担当します。

`SearchPageBuilder`が初期state・最近の履歴・asset version・メッセージ・SEOを組み立て、`SearchHtmlResponse`／`JsonResponse`がheaderと出力を担当します。依存構築とAction登録は、本番入口とHTTPテストで共有する`SearchControllerFactory`へ移しました。

分離前に`tests/http.php`でGET／POSTのactive tab・validation error・HTTP status・`X-Robots-Tag`を固定し、分離後も同じテストが通過しています。`tests/search-actions.php`で入力エラー時の内部status（航空券／ホテルは`idle`、フェリーは`invalid`）、未設定時のstatus、正常フェリー検索、履歴DB障害時の継続も検査します。JSON応答でCSRFを更新しない契約も固定しています。外部APIの実通信はこのテストの対象外です。

### P1-2. `extract()`と暗黙View変数を廃止する（対応済み）

検索Actionの返却配列を`FlightSearchViewData`／`HotelSearchViewData`／`FerrySearchViewData`へ置き換え、全プロパティをreadonlyにしました。`SearchPageBuilder`はこの3型または初期状態の`null`だけを受け取り、検索種別からactive tabを決定して`SearchPageViewModel`を構築します。名前付きconstructor引数の誤記、未定義プロパティの参照・追加、構築後の変更は例外になります。

`SearchHtmlResponse`の`extract()`を除去し、全View／partialに型付きの`$page`を明示的に渡します。partialは`SearchView::render()`で個別に描画し、親Viewのローカル変数に依存しません。CSRF・POST判定・画面全体の検索結果状態もページモデルから取得し、Viewからのsuperglobal参照を除去しました。

変更前に記録した12ケースのHTML契約を`php tests/view-contract.php`で検証します。asset version・年・改行コードのみ正規化し、正常結果・入力エラー・未設定・空結果・障害時のHTML全体を比較します。7件表示時の「もっと見る」、画像fallback、class／data属性、escapeも対象です。通常テストには誤記・変更の拒否、明示モデルだけでのpartial描画を追加しました。

今回の型付け対象はActionからViewへ渡す画面stateです。入力値の固定key配列、正規化済みの結果レコード、SEOなどの内側の配列は既存契約を維持し、個々のレコードのクラス化は別の段階とします。

### P1-3. 起動時の依存構築をFactoryへ移す（対応済み）

`SearchControllerFactory`を領域別Factoryの組合せとAction登録に絞り、`app/Factories/`の`FlightSearchFactory`／`HotelSearchFactory`／`FerrySearchFactory`へ依存構築を分割しました。`ApifySearchFactory`が共通clientと領域別Normalizer・cache設定を構築します。cache path・TTLの既定値、個別設定、負のTTLを0にする挙動は維持しています。

共有は1回のController構築内に限定します。履歴Modelは航空券・ホテル・画面組立で共有し、FlightCityは航空券の検索とURL生成で共有します。フェリーのAction・地図処理は同じFerryRouteとFerrySearchServiceを使います。Apifyのclientを共有し、Normalizerとcacheは領域別です。staticなインスタンス保持や外部DIコンテナは導入していません。

Controllerは`SearchPageBuilderInterface`と既存のcallable Action登録を受け取り、テストではfakeを直接渡せます。`handle(method, input, sessionToken)`はsuperglobalに依存せずResponseを返し、`index()`がSession反映と送信を担当します。`tests/controller-factory.php`でHTML／JSON振り分け、入力・CSRF引渡し、GET・未知POST・既定検索種別、依存共有、cache設定を検査します。既存HTTPテストとHTML契約12ケースも通過しています。

### P1-4. Provider別URL Builderへ分割する（対応済み）

`FlightUrlBuilder`をproviderの選択とリンク順序を管理する入口にし、`app/Services/FlightUrls/`のExpedia／Agoda／Airtrip／Travelist／RealTicket／Jtb／SkyTicket／SkyGateの各Builderへ都市code・日付形式・往復／片道・人数の処理を移しました。公開メソッドとリンクkey・順序、fallback、既存パラメータを維持しています。

共通の`FlightUrl`が既存のRFC3986 query生成とURL検証を担当します。各Builderの結果とMaps URLを検証し、HTTPSの絶対URL以外、認証情報・空白・制御文字・バックスラッシュを含むURLは拒否します。provider固有のパラメータや文字列は書き換えません。

分離前の出力を`tests/fixtures/flight-urls.json`へ記録し、`php tests/flight-url-golden.php`で10ケースを完全比較します。国内／海外、往復／片道、都市圏／空港、SEL→ICN、alias、特殊文字、未知都市fallback、年跨ぎ、人数を含みます。SkyGateのUUIDのみ比較用に正規化し、v4形式と重複がないことを別途検査します。通常テストに共通URL検証とquery生成の契約も追加しました。

今回providerの追加・削除やaffiliateパラメータの変更は行っていません。未表示・未使用だったTrip.com、Booking.com、ena、さくらトラベルは削除済みのままです。今後の削除候補はaffiliate契約・再表示予定を確認してから判断します。予約サイトへの実アクセスや最新仕様への適合は、この互換性リファクタリングの検証対象外です。

### P1-5. Normalizerを検索領域ごとに分割する（分離済み・実response補完待ち）

分離前に`tests/fixtures/normalizers/`へ入力と期待出力を記録し、出力key・値・型・順序を完全比較する契約testを追加しました。ホテルはローカルcacheの実responseを使用し、識別情報を置き換えて必要fieldだけを残しています。航空券・目的地候補は実responseが見つからなかったため合成fixtureです。由来と加工範囲は同ディレクトリのREADMEに記録しています。

`app/Services/Normalizers/`の`HotelResponseNormalizer`／`DestinationResponseNormalizer`／`FlightResponseNormalizer`へ処理を分離しました。価格parseとHTTPS URLの既存判定だけを`NormalizedValue`で共有し、ホテル料金の再帰parse・座標alias、航空券のsegment・並び順・時刻処理、候補数制限は領域内に保持します。既存の変換挙動は変更していません。

検索Serviceと`ApifySearchFactory`は領域別Normalizerを直接使います。旧`ApifyResponseNormalizer`は既存呼出との互換用に委譲だけを残し、同じfixtureで両経路を検証します。ホテルの予約リンク・航空会社metadataなど後段で追加するkeyは既存ServiceテストとHTML契約で保護します。

残作業: 航空券・目的地候補の匿名化した実responseを追加し、現行Actorとの適合を確認すること。今回外部APIは呼び出しておらず、合成fixtureを実responseとして扱っていません。

### P1-6. フェリー地図のデータと表示責務を整理する（対応済み）

地域・都道府県・港の正規名・alias・所属地域・画像座標・照合優先順位を`database/ferry-map.json`へ移しました。`FerryMapMaster`が座標範囲、地域参照、名前・alias・都道府県・優先順位の重複を検証してprojectionを返します。`FerryMapService`は航路の表示データと出発／到着projectionの組立に限定し、Factoryからmasterを渡します。

分離前の港projectionをfixture化し、全登録港・47都道府県の中央fallback・重複する部分一致・未知／空欄／末尾空白の都道府県・港と都道府県の矛盾をテストで固定しました。未知都道府県は既知港名でも`overseas`中央に配置します。表示名はDB原文を維持し、地域は航路の都道府県を優先します。alias照合は明示したpriority順の部分一致です。

`database/ferry-map.md`にfallbackと更新手順を記載しました。`app.js`の選択・向き反転・描画責務とJSON endpointのkeyは維持し、Serviceテストで出発／到着・label・route ID・予約先の契約を確認しています。

### P1-7. `app.js`を機能単位に分割する（対応済み）

分離前に、実際のPHP Viewを描画したDOM fixtureとheadless Chromeによるbrowser testを追加しました。APIをfakeにして、tab／旅行タイプ・履歴再入力／ホテル候補／フェリー候補・航路／地図選択・向き反転／結果展開・画像fallback／loadingの7領域を検査し、分離後も通過しています。loadingには二重送信防止、submitter値の保持、pageshowでの復元・完了表示も含みます。

`app.js`を初期化専用にし、`public/assets/js/`の8つのES moduleへ分割しました。各初期化関数はdocumentを明示的に受け取り、同じDOMへの二重初期化を防ぎます。フェリー地図のloader共有はDOM上の`_loadFerryMap`からmodule内のWeakMapへ移しました。selector・責務・初期化順序は同ディレクトリのREADMEに記載しています。

ビルドツール・npm依存は追加していません。PHPで全JSの内容から共通versionを生成し、entryから各importへ引き継ぎます。モジュール取得中にpageshowが発生した場合もloadingを復元します。HTML契約は意図的な`type="module"`の追加だけを別途検査して正規化し、それ以外の描画結果は従来の12ケースと一致します。

`node tests/browser.mjs`はNode.js 24、PHP、Chrome／Chromiumが必要です。GitHub Actionsにも追加しました。外部APIとの実通信や全ブラウザでの互換性検証は対象外です。

## 4. P2: 整理候補

1. Request間のCSRF、文字数、整数、厳密日付Validationを小さな共通部品へ寄せる。
2. `FerryController`のJSON出力と`destinationSuggestions()`のJSON出力を共通Responseへ寄せ、`JSON_THROW_ON_ERROR`と失敗時statusを統一する。
3. `HotelSearchService::addHotelCardLinks()`内外で重複する例外処理を一つの責任境界へ寄せる。
4. `ApiCache`の期限切れfile・lock file・一時fileの清掃、容量上限、stale-if-errorを設計する。
5. `ApiCache`の`monthlyLimit`／`usageFile`は現行の生成箇所から渡されず、実質未使用です。設定と運用要件を確認して接続または削除する。
6. ホテル候補の`hotel_place_*`とNormalizerの`property_token`は取得・保持されますが、検索条件やViewで利用されません。将来用途を確認して契約を縮小する。
7. `FlightCity::airportCandidates()`などの未使用methodを参照test付きで棚卸しする。Trip.com、Booking.com、ena、さくらトラベルの未表示処理と専用設定は削除済み。
8. 一部ファイルだけにある`strict_types`と、圧縮された一行形式のService／Viewをproject規約として統一する。
9. error logへrequest ID、機能名、外部service、HTTP status、例外classを付与し、同じ検索内の障害を追跡可能にする。
10. migrationが今後増える段階で、適用順と適用済みversionを管理するrunnerの導入を検討する。現行migrationの既存DBへの適用は完了済み。

## 5. 仕様確認が必要な項目

### 子供条件

ホテル検索は子供の人数だけを受け取り、Apifyには全員8歳として送ります。OTAごとに子供条件の反映方法も異なります。年齢入力を追加するか、「参考検索」として制約を表示するかを先に決めます。Form、Request、cache key、履歴、Apify、全ホテルOTAへ波及します。

### ホテルの「公式サイト」

`official_url`はActorの`link`由来で、公式domainか検証していません。保証できない場合は「詳細を見る」等へ名称変更するか、信頼できる別masterで公式URLを管理します。

### フェリー検索履歴

航空券・ホテルは履歴保存されますが、フェリーは保存されません。最近の検索へ含めるか、意図的に対象外とするかを仕様として明記します。

### フェリー料金・航路情報の鮮度

フェリー情報はDB seedのsnapshotで、実運航・空席のAPI検索ではありません。`fare_updated_at`が空のrouteも多いため、表示上の免責、更新担当、更新周期、休廃止routeの無効化手順を決めます。

## 6. 解消済み・維持対象

- `iata_cities`のDDL・Index・seed欠落は`schema.sql`では解消済みです。
- 全利用者共通だった検索履歴は、256-bit匿名`visitor_id`と複合Indexによる利用者分離で解消済みです。
- API tokenをbrowserへ出さない構造、prepared statement、基本的なCSRF／日付／人数検証は維持対象です。
- 楽天障害をApifyホテル結果から隔離する方針、cacheのkey lockと一時file経由の書込、画像／航空会社logo fallbackは維持対象です。
- フェリー会社IDと航路IDの所属をserver側で再検証しており、hidden fieldを信用していない点は維持対象です。

## 7. 技術的負債 TOP 5

1. `SearchController`／暗黙View state／単一`app.js`へ画面変更が集中している。
2. 子供条件・ホテル公式URL・フェリー情報の鮮度に未確定の仕様が残っている。
3. Provider固有URL生成とActor response変換が大きなクラスへ集中している。
4. cache清掃・容量上限・stale-if-errorがなく、長期運用時の制御が弱い。
5. Controller／View／JavaScriptを通したbrowser testとActor実response fixtureがない。

## 8. 推奨順

```text
Action／View Data／起動Factoryの境界整理
→ Provider Builder／Normalizer分割
→ app.jsとフェリー地図dataの分割
→ 未使用code・設定・取得fieldの整理
```

## 9. 機能追加・更新時の確認事項

1. DB変更時はfresh installと既存DB migrationの両方を確認する。
2. 新しい検索種別を`SearchController`の条件分岐と暗黙変数へ直接追加し続けない。
3. Actor変更時は匿名化した実response fixtureでNormalizerとView keyを確認する。
4. IATA／航空会社master変更時はApify入力、国内判定、集約、全航空券OTAを確認する。
5. フェリーmaster変更時は会社候補、所属検証、地図座標、方向反転、予約先URLを確認する。
6. Viewのclass、name、data属性変更時は`app.js`のselectorと履歴再入力を確認する。
7. 履歴列追加時はINSERT、SELECT、統合sort、View、data属性、再入力を確認する。
8. 子供条件はprovider間で同一ではない。
9. 「公式サイト」と表示するURLの出所とdomainを確認する。
10. README、保守マニュアル、config example、schema／migration、実装を同じreleaseで同期する。
