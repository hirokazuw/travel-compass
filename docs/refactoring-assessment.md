# Travel Compass リファクタリング診断・残作業一覧

対象: **V1.9.2のローカル実装**。P1-1〜P1-7の構造整理と、子供条件・ホテル詳細リンク・フェリー運用の仕様確定を反映しています。

今回は既存の診断項目を実装・テスト・確定仕様と照合して整理しました。新たな本番接続、外部API検証、Git履歴の監査は行っていません。本番配備の完了を示す文書ではありません。

## 1. 未対応・部分対応の一覧

**次に着手する候補は、実responseの補完、cacheの長期運用対策、JSON失敗時の契約整理です。** 下表の順序は提案であり、未承認の仕様変更を実施するものではありません。P2番号は旧診断の項目番号を維持しています。

| 区分・ID | 状態 | 残っていること | 次の作業・完了の目安 |
|---|---|---|---|
| P2-4 | **未対応** | cacheの定期清掃・容量上限・期限切れデータを障害時に利用する方針がない | 保持期間・容量・障害時利用の可否を決め、安全な清掃手順とテストを整える。既存のlock・一時file書込は維持 |
| P2-2 | **部分対応** | JSONエンコード失敗時の例外・statusの扱いが不統一 | 共通`JsonResponse`で失敗時の応答契約を決め、不正UTF-8等のテストを追加する。Responseの共通化自体は完了 |
| P2-9 | **未対応** | 同じ検索内の障害を追う共通ログ情報がない | request ID、機能名、外部service、HTTP status、例外classの付与方針を整理する |
| P2-3 | **未対応** | ホテルカードリンク生成の例外処理がActionとServiceに分散 | カード単位の失敗と処理全体の失敗を区別し、必要な境界を残して重複を整理する |
| P2-1 | **未対応** | Request間のValidationが重複 | CSRF・文字数・整数・厳密日付の共通部品候補を整理する。既存エラー内容とstatusの契約を維持 |
| P2-5 | **用途確認待ち** | `ApiCache`の`monthlyLimit`／`usageFile`が生成箇所から渡されていない | 月次制限が必要なら設定と運用へ接続し、不要なら参照確認後に削除する |
| P2-6 | **用途確認待ち** | `hotel_place_*`と`property_token`は保持されるが、検索条件などに未利用 | 将来用途を確認し、維持するfieldと削除するfieldを決める。DOM・Normalizer契約も同時に更新 |
| P2-7 | **棚卸し待ち** | `FlightCity::airportCandidates()`など未使用method候補が残る | 呼出箇所・テスト・将来用途を確認して整理する。未表示providerの削除済み処理は再度課題にしない |
| P2-8 | **未対応** | `strict_types`や一行に圧縮されたコードの書式が不統一 | 小さな規約を決め、対象を絞って統一する。動作変更と混在させない |
| P2-10 | **将来検討** | migration runnerがない | migrationが増える時点で適用順・適用済み管理を検討する。既存migration未適用の意味ではない |

### 実装不足と分けて扱うもの

| 種類 | 現状・扱い |
|---|---|
| 継続運用 | フェリー情報を管理者が3月・9月と変更把握時に手動確認する。運用方針と表示は実装済み。各回の実施状況は別途記録する |
| 検証範囲の拡張 | 外部APIの実通信、予約サイトの最新仕様との適合、複数ブラウザ、障害パターンの追加は未検証範囲。主要DOMのbrowser testは既にある |
| 型付けの追加候補 | 入力値・正規化結果・SEOの内側には配列が残る。ページ全体と検索種別のreadonly化は完了。個々のレコードのクラス化は必要性を見て判断する |

### 現時点の検証基盤

V1.9.2準備時に、通常テスト39件、HTTP characterization、HTML契約12ケース、URL golden test10ケース、ChromeのDOMテスト7領域、PHP全92ファイルの構文検査が成功しています。今回の文書整理ではテストを再実行していません。

MySQL baselineの検査はCIに定義済みです。このローカル確認で本番DBやCI実行結果を再確認したものではありません。

以降は、対応済みの内容と確定仕様の記録です。**未対応作業は上の一覧を参照してください。**
## 2. P0: リリース・再現性・可用性

### P0-1. DB初期構築を現行機能と一致させる（対応済み）

- 対象: `database/schema.sql`, `database/migrations/archive/`, README
- 対応: 現DBの構造dumpを照合し、`schema.sql`へ`airlines`, `ferry_companies`, `ferry_routes`のDDLと現行seedを統合しました。検索履歴・`iata_cities`を含むfresh install用baselineです。履歴の実データは含めていません。
- 運用: 新規環境にはbaselineを一括適用します。既存DBへの現行migration適用は完了しており、統合済みSQLは`database/migrations/archive/`へ保管しています。baselineやarchive内のSQLを既存DBへ再適用しません。
- 検証: 空DBへのbaseline適用と外部キー・主要seed件数などをCIで検査します。baselineは新規環境用で、既存DBへ再適用しません。

### P0-2. 履歴DB障害を検索・初期画面から分離する（対応済み）

- 対象: `FlightSearchAction`, `HotelSearchAction`, `SearchPageBuilder`, `SearchHistory`
- 対応: `createFlight()`／`createHotel()`と`recent()`を個別のbest-effort処理にし、例外時は機能別のerror logを残して検索・描画を継続するようにしました。
- 保存時点: 従来どおりValidation成功直後に保存を試みるため、履歴は「検索成功履歴」ではなく「有効な入力履歴」です。
- 障害時: INSERT失敗時も航空券・ホテル検索を継続し、SELECT失敗時は最近の検索を空として画面を描画します。
- 検証: INSERT／SELECT失敗時の継続をテスト済みです。外部検索成功／失敗と組み合わせた障害網羅は追加検証候補です。

### P0-3. 環境固有設定をGit管理から分離する（完了）

- 対象: `config/config.php`, `.vscode/sftp.json`, `.gitignore`
- 対応: `config/config.php`と`.vscode/sftp.json`をローカルに残したままGitの追跡対象から外し、`.gitignore`へ追加しました。`config/config.example.php`は引き続き追跡します。
- setup: 新規環境では`config/config.example.php`を`config/config.php`へコピーし、環境固有値を設定します。
- 確認: ユーザーによる目視確認済みとの報告を受け、credentialの失効・再発行要否の確認を含めP0-3を完了としました。今回、追加の監査や過去のGit履歴の書き換えは行っていません。

### P0-4. 最低限の自動test基盤を作る（対応済み）

- 対象: Requests、`ApifyResponseNormalizer`、`FlightOfferAggregator`、URL Builders、`FlightCity`、フェリーService／Model契約
- 対応: `php tests/run.php`で動く軽量runnerを追加し、外部通信や本番設定なしで39件のtestを実行できます。
- 対象: 日付・人数・CSRF、ホテル／目的地／航空券response正規化、価格parse、ホテル／航空券URL、IATA都市圏、航空会社集約、ホテル名寄せ、フェリー会社と航路ID、地図用route変換、baseline構成。
- DB: 通常testはインメモリSQLiteを使用します。GitHub ActionsではMySQL 8の空DBへ`database/schema.sql`を投入し、6テーブル、master件数、外部キー、履歴data非混入を検査します。
- 検証拡張: Controllerのfakeテスト、HTML契約、主要DOMのbrowser testは追加済み。Actor実responseの不足と障害系の追加は先頭の一覧で管理します。

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

### P1-5. Normalizerを検索領域ごとに分割する（対応済み）

2026-09-15：提供された `docs/airticket.json` から航空券の実response由来fixtureを追加しました。元JSONと本番実装は変更せず、代表3件のkey・値・型・順序を領域別Normalizerと互換窓口で検証します。詳細は `tests/fixtures/normalizers/README.md` に記載しています。

分離前の合成fixtureは境界条件の検証用として維持します。ホテルと航空券は実response由来fixtureもあります。航空券の出発地・目的地候補はApifyではなく `iata_cities` を利用し、日本語・英語・IATAコード検索、10件一致時の8件上限、選択コードの受け渡しをテストします。

`app/Services/Normalizers/`の`HotelResponseNormalizer`／`DestinationResponseNormalizer`／`FlightResponseNormalizer`へ処理を分離しました。価格parseとHTTPS URLの既存判定だけを`NormalizedValue`で共有し、ホテル料金の再帰parse・座標alias、航空券のsegment・並び順・時刻処理、候補数制限は領域内に保持します。既存の変換挙動は変更していません。

検索Serviceと`ApifySearchFactory`は領域別Normalizerを直接使います。旧`ApifyResponseNormalizer`は既存呼出との互換用に委譲だけを残し、同じfixtureで両経路を検証します。ホテルの予約リンク・航空会社metadataなど後段で追加するkeyは既存ServiceテストとHTML契約で保護します。

分離したホテル候補の実response検証も2026-09-15に対応済みです。`docs/destination.json` の全5件を匿名化したfixtureで、keywordのみの候補と座標付き候補の7出力key・値・型・順序を検証しました。欠損値・null・空配列・未知field・件数制限のテストも追加し、Normalizerの修正は不要でした。航空券候補用のApify JSONは不要です。実response契約は提供された例を対象とし、全Actor形式や外部APIの稼働を保証するものではありません。

### P1-6. フェリー地図のデータと表示責務を整理する（対応済み）

地域・都道府県・港の正規名・alias・所属地域・画像座標・照合優先順位を`database/ferry-map.json`へ移しました。`FerryMapMaster`が座標範囲、地域参照、名前・alias・都道府県・優先順位の重複を検証してprojectionを返します。`FerryMapService`は航路の表示データと出発／到着projectionの組立に限定し、Factoryからmasterを渡します。

分離前の港projectionをfixture化し、全登録港・47都道府県の中央fallback・重複する部分一致・未知／空欄／末尾空白の都道府県・港と都道府県の矛盾をテストで固定しました。未知都道府県は既知港名でも`overseas`中央に配置します。表示名はDB原文を維持し、地域は航路の都道府県を優先します。alias照合は明示したpriority順の部分一致です。

`database/ferry-map.md`にfallbackと更新手順を記載しました。`app.js`の選択・向き反転・描画責務とJSON endpointのkeyは維持し、Serviceテストで出発／到着・label・route ID・予約先の契約を確認しています。

### P1-7. `app.js`を機能単位に分割する（対応済み）

分離前に、実際のPHP Viewを描画したDOM fixtureとheadless Chromeによるbrowser testを追加しました。APIをfakeにして、tab／旅行タイプ・履歴再入力／ホテル候補／フェリー候補・航路／地図選択・向き反転／結果展開・画像fallback／loadingの7領域を検査し、分離後も通過しています。loadingには二重送信防止、submitter値の保持、pageshowでの復元・完了表示も含みます。

`app.js`を初期化専用にし、`public/assets/js/`の8つのES moduleへ分割しました。各初期化関数はdocumentを明示的に受け取り、同じDOMへの二重初期化を防ぎます。フェリー地図のloader共有はDOM上の`_loadFerryMap`からmodule内のWeakMapへ移しました。selector・責務・初期化順序は同ディレクトリのREADMEに記載しています。

ビルドツール・npm依存は追加していません。PHPで全JSの内容から共通versionを生成し、entryから各importへ引き継ぎます。モジュール取得中にpageshowが発生した場合もloadingを復元します。HTML契約は意図的な`type="module"`の追加だけを別途検査して正規化し、それ以外の描画結果は従来の12ケースと一致します。

`node tests/browser.mjs`はNode.js 24、PHP、Chrome／Chromiumが必要です。GitHub Actionsにも追加しました。外部APIとの実通信や全ブラウザでの互換性検証は対象外です。

## 4. P2の扱い

P2の全10項目は先頭の残作業一覧へ統合しました。共通JSON Responseへの移行済み部分と、エンコード失敗時の未対応部分を分けて管理します。

## 5. 確定仕様（再検討・追加実装は不要）

### 子供条件（仕様確定）

ホテル検索は子供の人数だけを受け取り、子供は全員8歳として扱います。Apifyには人数分の8歳を送る既存実装を維持します。年齢入力は追加せず、「参考検索」としての子供条件の制約表示も追加しません。OTAごとの子供条件の反映方法は現行のままとし、この方針に伴うForm、Request、cache key、履歴の変更は不要です。

### ホテルの詳細リンク（対応済み）

`official_url`はActorの`link`由来で、公式domainか検証していないため、ホテルカードのボタン表示を「公式サイト」から「詳細を見る」へ変更しました。リンク先と内部のkey・classは維持します。

### フェリー検索履歴（仕様確定）

検索履歴の対象は航空券・ホテルのみとします。フェリーは条件検索・地図検索ともに意図的に対象外とし、履歴を保存せず、「最近の検索」にも含めません。現行実装を維持し、履歴用テーブルや再入力機能は追加しません。

### フェリー料金・航路情報の鮮度（仕様確定・表示対応済み）

フェリー情報はDBに登録した参考情報で、実運航・空席のAPI検索ではありません。条件検索と地図検索の結果付近に「運賃・ダイヤ・運航状況は参考情報です。最新情報・空席状況は各フェリー会社公式サイトでご確認ください。」を常時表示します。`fare_updated_at`がある場合は「料金確認日：2026/08/22」の形式で表示し、空の場合は確認日表示を省略します。

サイト管理者が手動更新します。定期確認は半年に1回（3月・9月）とし、公式発表などで変更を把握した場合は随時更新します。料金体系・季節運賃は公式サイトで確認し、自動取得は追加しません。

休止・廃止航路は`ferry_routes.active = 0`にして記録を保持し、物理削除しません。再開時は情報を確認して`active = 1`へ戻します。条件検索・航路候補・地図は有効な航路だけを表示する現行実装を維持します。運用手順は保守マニュアルに記載しています。

## 6. 解消済み・維持対象

- `iata_cities`のDDL・Index・seed欠落は`schema.sql`では解消済みです。
- 全利用者共通だった検索履歴は、256-bit匿名`visitor_id`と複合Indexによる利用者分離で解消済みです。
- API tokenをbrowserへ出さない構造、prepared statement、基本的なCSRF／日付／人数検証は維持対象です。
- 楽天障害をApifyホテル結果から隔離する方針、cacheのkey lockと一時file経由の書込、画像／航空会社logo fallbackは維持対象です。
- フェリー会社IDと航路IDの所属をserver側で再検証しており、hidden fieldを信用していない点は維持対象です。

## 7. 次に進める順序の提案

1. 実response検証は提供例について完了。以下の運用・障害時契約の整理へ進む。
2. P2-4・P2-5: cache保持・容量・利用制限の運用要件を決める。
3. P2-2・P2-9・P2-3: JSON失敗時の契約、ログ、例外処理の境界を整える。
4. P2-1・P2-6〜P2-8: Validation、取得field、未使用method、書式を小さく整理する。

migration runnerは必要になった段階で検討します。

## 8. 対応済み項目の要約

| 対応範囲 | 現状 |
|---|---|
| P0-1〜P0-4 | DB baseline、履歴障害の分離、設定の管理分離、テスト基盤を整備済み。P0-3はユーザーの目視確認をもって完了。追加検証候補は各項目に記載 |
| P1-1〜P1-4 | Action／Response、readonly View Model、領域別Factory、provider別URL Builderへ移行済み |
| P1-5 | 分割・ホテル／航空券／ホテル目的地候補の実response契約・航空券DB候補の上限テストは完了 |
| P1-6〜P1-7 | フェリー地図masterとJavaScriptの機能別moduleへ移行済み |
| V1.9.2の仕様確認 | 子供条件、ホテル詳細リンク、フェリー履歴対象外、更新運用・免責・確認日表示を確定済み |
## 9. 機能追加・更新時の確認事項

1. DB変更時はfresh installと既存DB migrationの両方を確認する。
2. 新しい検索種別を`SearchController`の条件分岐と暗黙変数へ直接追加し続けない。
3. Actor変更時は匿名化した実response fixtureでNormalizerとView keyを確認する。
4. IATA／航空会社master変更時はApify入力、国内判定、集約、全航空券OTAを確認する。
5. フェリーmaster変更時は会社候補、所属検証、地図座標、方向反転、予約先URLを確認する。
6. Viewのclass、name、data属性変更時は`public/assets/js/`の該当moduleと履歴再入力を確認し、browser testを実行する。
7. 履歴列追加時はINSERT、SELECT、統合sort、View、data属性、再入力を確認する。
8. 子供は人数のみ・全員8歳の確定仕様を維持する。年齢入力・子供条件の制約表示は追加しない。
9. Actor由来のホテルリンクは「詳細を見る」の表示を維持する。
10. README、保守マニュアル、config example、schema／migration、実装を同じreleaseで同期する。
