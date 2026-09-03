# Travel Compass リファクタリング診断

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

### P1-1. HTTP Actionと画面組立を分離する

`SearchController`は、3検索種別のdispatch、JSON endpointのdispatch、初期state、履歴、asset version、SEO、header、View読込を担当しています。フェリー処理自体は`FerryController`へ分離済みですが、入口は`search_type`の条件追加方式のままです。

検索種別ごとのActionと、HTML／JSON Response、共通の画面state組立へ分ける候補です。先にPOST後のactive tab、status、validation error、`X-Robots-Tag`をcharacterization testで固定します。

### P1-2. `extract()`と暗黙View変数を廃止する

`SearchController::index()`は3つのhandler返却配列を`EXTR_OVERWRITE`で展開し、Viewは多数の暗黙変数へ依存します。返却keyの誤記・追加が既存stateを静かに上書きし、検索種別が増えるほど影響範囲が読みにくくなります。

検索種別ごとのimmutableなView Dataと、ページ全体のView Modelへ段階的に置き換える候補です。HTML構造と`app.js`が参照するclass／data属性を契約として保護します。

### P1-3. 起動時の依存構築をFactoryへ移す

`public/index.php`が設定解釈、cache path／TTL、全Model・Service・Controller生成を直接担当しています。同じ`FerryRoute`から`FerrySearchService`を2回生成しており、依存の共有方針も暗黙です。

外部libraryのDIコンテナ導入を急ぐ必要はありません。検索領域ごとの小さなFactoryへ移し、Controller testでfakeを渡せる境界を作る候補です。

### P1-4. Provider別URL Builderへ分割する（継続）

`FlightUrlBuilder`はprovider固有の都市code、日付形式、往復／片道、人数を1クラスに保持しています。未表示・未使用だったTrip.com、Booking.com、ena、さくらトラベルの処理は削除済みです。

provider別Builderと共通URL検証へ分け、現行出力をgolden testで固定します。削除候補はaffiliate契約・再表示予定を確認してから判断します。

### P1-5. Normalizerを検索領域ごとに分割する（継続）

`ApifyResponseNormalizer`はホテル、目的地候補、航空券を扱います。Actor変更の理由が異なるため、領域別Normalizerに分ける候補です。単なるファイル分割より先に、実response fixtureとViewへ渡すkeyの契約を作ります。

### P1-6. フェリー地図のデータと表示責務を整理する（新規）

`FerryMapService`が都道府県→地方、港名の部分一致→画像座標をPHP定数で保持し、`app.js`が地図選択、向き反転、結果表示を担当します。港名追加・表記変更が座標fallbackや地域分類へ暗黙に影響します。

港の正規化名・地域・座標を検証可能なmaster dataへ寄せ、Serviceはroute projectionに限定する候補です。未知都道府県がすべて`overseas`扱いになる現在のfallbackも仕様化します。

### P1-7. `app.js`を機能単位に分割する（優先度更新）

811行の単一scriptにtab、ホテル候補、フェリー候補・航路、フェリー地図、画像fallback、履歴再入力、loadingが集約されています。DOM selectorと状態遷移が暗黙のため、View変更時の回帰範囲が広い状態です。

build tool導入を前提にせず、初期化関数またはES moduleを機能単位に分ける候補です。先に主要DOM fixtureによるbrowser testを追加します。

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
