# Travel Compass リファクタリング診断

調査基準: `master` / `1fc8393d5e0cb7bb9e6716b751b1ea16f155eb33`

## 1. 現状診断

- 責務過多: `SearchController`, `FlightUrlBuilder`, `ApifyResponseNormalizer`, `HotelSearchService`, `public/index.php`
- 重複: 日付・整数Validation、HTTPS検証、cURL／JSON処理、error log、ホテルForm、OTA日付・人数変換
- 密結合: Controllerとsuperglobal/header/require、Viewと配列key、JSとselector、`FlightCity`とDB schema
- 未使用: `travel_searches`, `sakura()`, `airportCandidates()`, 非表示Trip.com／Booking.com／ena、`affiliate.hotel_url`, `hotel_place_*`, property token等
- error課題: 広い`catch(Throwable)`、構造化logなし、cache失敗黙殺、履歴障害が検索停止、stale cacheなし
- test困難: 手動`new`、static、superglobal、cURL／PDO／filesystem具象依存、暗黙View変数、test/CIなし

Scrape.do、SerpAPI、Travelpayoutsの現行実装や、大規模なコメントアウト済み旧処理は確認できません。

## 2. P0

### P0-2. 検索履歴の公開範囲

- 対象: `VisitorIdCookie`, `SearchHistory`, `SearchController`, 履歴テーブル
- 対応済み: 256-bitの匿名`visitor_id`を90日Cookieへ保存し、履歴の保存・取得を利用者単位に分離
- Cookie属性: `HttpOnly`, `SameSite=Lax`, HTTPS通信時は`Secure`
- DB: 航空券・ホテル履歴へ`visitor_id`列と複合Indexを追加
- privacy: Cookieには検索条件を保存せず、推測困難な匿名IDのみ保存
- migration: 所有者を特定できない既存履歴は削除してから新しい列を追加

### P0-3. 環境固有設定をGitから分離

- 対象: `config/config.php`, `.vscode/sftp.json`
- 現状: 実設定とSFTP環境情報がGit管理
- 問題: 接続先、user、秘密鍵path等が公開される
- 改善: exampleのみ管理し実設定をignore、公開済み情報を監査
- 影響: deployment、local setup
- risk: 本番setupを先に確認する必要

### P0-4. 最低限の自動test

- 対象: Requests、Normalizers、URL Builders、`FlightCity`
- 現状: testなし
- 問題: Actor／OTA変更とrefactorの回帰を検知不能
- 改善: 実response fixtureによる純粋処理testから開始
- 影響: 開発・release全体
- risk: 現状挙動が仕様かbugかを先に確定

### P0-5. 履歴DB障害と検索を分離

- 対象: `handleFlightSearch()`, `handleHotelSearch()`
- 現状: 履歴INSERT失敗で検索も停止
- 問題: 補助機能が主要機能の可用性を下げる
- 改善: 履歴失敗を個別処理するか検索後の非必須処理にする
- 影響: 両検索、log、履歴件数の意味
- risk: 保存timing変更

## 3. P1

### P1-1. Controller分割

`SearchController`をFlight／Hotel／Suggestion ActionとResponse DTOへ分割します。画面全体へ影響するため、active tab、status、POST後表示の回帰testが必要です。

### P1-2. OTA Provider分割

`FlightUrlBuilder`をProvider別Strategyへ分割します。全航空券buttonに影響し、既存URL encodingとquery形式の契約testが必要です。

### P1-3. Normalizer分割

`ApifyResponseNormalizer`をFlight／Hotel／Destinationへ分割します。Viewへ渡す配列keyの互換性に注意します。

### P1-4. 未使用code整理

`travel_searches`、未使用method、非表示link、未使用設定、README不一致を整理します。affiliate上の将来利用を確認してから削除判断します。

### P1-5. 子供条件の明示・統一

Apifyの全員8歳固定とProvider別の人数反映差を整理します。Form、DB、cache key、全ホテルOTAへ影響します。

### P1-6. 「公式サイト」の意味

Actorの`link`が公式siteか検証し、保証できなければ「詳細を見る」等へ変更します。全ホテルカードへ影響します。

## 4. P2

1. Request間の共通Validation
2. Apify／楽天の共通HTTP client
3. View DTO化と`extract()`廃止
4. 国内／海外ホテルForm重複削減
5. cache清掃、容量上限、stale-if-error

## 5. 技術的負債 TOP 5

1. `iata_cities` schema・seed欠落（解消済み）
2. 全利用者共通履歴（匿名Cookie IDによる分離で解消済み）
3. 実設定・SFTP情報のGit管理
4. 自動testなし
5. 履歴DB障害が検索停止

## 6. 推奨順

```text
DB再現性・履歴公開範囲・設定管理
→ test
→ 履歴と検索の障害分離
→ Controller分割
→ OTA Builder分割
→ Normalizer分割
→ 未使用code
→ 子供条件
→ View DTO
```

## 7. 今すぐ触らなくてよい箇所

- `Env::load()`
- `Database::connect()`
- `SearchViewData`
- `SeoViewData`
- 画像／航空会社logo fallback
- 基本的なCSRF・日付・人数検証
- API tokenをbrowserへ出さない構造
- 楽天障害をホテル本体から隔離する方針

## 8. 機能追加時の注意

1. 新検索種別を`SearchController`へ追加し続けない。
2. Actor変更時は実response fixtureでNormalizerを確認する。
3. IATA変更時はApify、国内判定、全航空券OTAを確認する。
4. Viewのclass、name、data属性変更時は`app.js`を確認する。
5. 履歴列追加時はINSERT、SELECT、View、data属性、再入力を確認する。
6. 子供条件はProvider間で同一ではない。
7. 「公式サイト」と表示するURLの出所を検証する。
8. README、config example、実装を同時に同期する。
