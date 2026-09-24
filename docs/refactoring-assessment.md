# Travel Compass リファクタリング診断・残作業一覧

対象: **V1.9.4のローカル実装**。P1-1〜P1-7の構造整理、航空券のDB候補検索と表示名・検索値の分離、実response契約の補完、P0-3の目視確認完了を反映しています。V1.9.2で確定した子供条件・ホテル詳細リンク・フェリー運用の仕様も維持しています。

V1.9.4ではP2-1〜P2-9の対応と検証結果を反映しました。新たな本番接続、外部API検証、Git履歴の監査は行っていません。本番配備の完了を示す文書ではありません。

## 1. 未対応・部分対応の一覧

**次の運用作業は、配備時のcache清掃ジョブ登録です。** 下表の順序は提案であり、未承認の仕様変更を実施するものではありません。P2番号は旧診断の項目番号を維持しています。

| 区分・ID | 状態 | 残っていること | 次の作業・完了の目安 |
|---|---|---|---|
| P2-4 | **実装・手順対応済み** | cacheの保持・容量・障害時利用の方針を確定 | 期限切れ利用なし、ディレクトリごと100 MiB・1,000件、lock保護付き清掃CLIとテストを追加。本番の毎時実行登録は配備時作業 |
| P2-2 | **対応済み** | JSONエンコード失敗時の応答を統一 | 共通`JsonResponse`でHTTP 500と固定JSONエラーを返す。不正UTF-8・循環参照・深さ超過・非対応値のテストとHTTP応答検証を追加 |
| P2-9 | **対応済み** | 検索処理の共通JSONログを導入 | request ID・機能名・外部service・外部HTTP status・応答status・例外classを記録。機密情報を除外し、対象範囲を文書化 |
| P2-3 | **対応済み** | ホテルカードリンク生成の例外境界を整理 | カード単位の失敗はServiceで空リンクへ退避し継続。Actionの重複catchを除去し、検索全体・楽天取得の例外境界を維持 |
| P2-1 | **対応済み** | Request間のValidationを共通化 | `RequestValidation`へCSRF・文字数・整数・厳密日付の判定を集約。既存エラー内容・順序・status・入力値の正規化を維持 |
| P2-5 | **対応済み** | 未使用の月次制限を削除 | ユーザー確認により月次制限は不要。生成箇所の参照確認後、`monthlyLimit`／`usageFile`と利用回数カウンター処理を削除 |
| P2-6 | **対応済み** | 未使用のホテル・目的地メタデータを削除 | ユーザー確認により両方不要。hidden項目・JavaScript・Normalizer出力を整理し、DOM・Normalizer契約を更新 |
| P2-7 | **対応済み** | 未使用method候補を棚卸し | 将来用途なしの確認を受け、呼出参照のない`FlightCity::airportCandidates()`を削除。利用中の補助method・動的呼出は維持 |
| P2-8 | **対象範囲対応済み** | PHP書式の小規約を定義しApify関連4クラスへ適用 | `docs/php-style.md`に規約を記載。空白・改行だけを整理し、既存のstrict宣言有無を維持。全体統一・strict追加は別変更 |
| P2-10 | **将来検討** | migration runnerがない | migrationが増える時点で適用順・適用済み管理を検討する。既存migration未適用の意味ではない |

### 実装不足と分けて扱うもの

| 種類 | 現状・扱い |
|---|---|
| 継続運用 | フェリー情報を管理者が3月・9月と変更把握時に手動確認する。運用方針と表示は実装済み。各回の実施状況は別途記録する |
| 検証範囲の拡張 | 外部APIの実通信、予約サイトの最新仕様との適合、複数ブラウザ、障害パターンの追加は未検証範囲。主要DOMのbrowser testは既にある |
| 型付けの追加候補 | 入力値・正規化結果・SEOの内側には配列が残る。ページ全体と検索種別のreadonly化は完了。個々のレコードのクラス化は必要性を見て判断する |

### 現時点の検証基盤

V1.9.4の検証はREADMEのリリース記録を参照してください。通常テスト・HTTP応答・HTML契約・航空券URL・ブラウザDOM・PHP構文検査を対象とします。

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
2. P2-4は実装・手順対応済み。本番配備時に清掃CLIの毎時実行を登録する。P2-5は月次制限不要の確認を受け、未使用処理を削除済み。
3. P2-9の共通ログは対応済み。P2-2のJSON失敗時の応答契約、P2-3のホテルカードリンクの例外境界整理は対応済み。
4. P2-8は小規約とApify関連4クラスの書式整理まで対応済み。今後は変更するファイルに規約を適用する。P2-1・P2-6・P2-7も対応済み。

migration runnerは必要になった段階で検討します。

## 8. 対応済み項目の要約

### P2-9: 検索単位の共通ログ（2026-09-24）

`RequestLog`でControllerの検索処理ごとにrequest IDを発行し、既存の障害ログを共通JSON形式へ移行しました。機能名・event・外部service・外部HTTP status・アプリ応答status・例外classを記録します。Apify／楽天のHTTP例外は専用型でstatusを渡し、例外メッセージや検索入力はログへ含めません。[ログ方針](request-logging.md)に各項目、コンテキストの寿命、Controller外・描画／送信の対象外範囲を記載しました。

検証: 通常テスト61件、HTTP応答、HTML契約12ケース、PHP構文検査が成功。追加テストで複数障害と応答のID一致、検索間のID分離、例外後の復元、外部429とアプリ502の区別、機密文字列の非出力、JSON失敗時の関連付けを確認しました。外部APIの実通信は行っていません。

### P2-8: 小規模な書式統一（2026-09-24）

[PHP書式の小規約](php-style.md)を定義し、`ApifyClient`・`ApifyFlightSearch`・`ApifyHotelSearch`・`ApifyDestinationSearch`へ適用しました。宣言間の空行、メソッド本文、長い配列・呼出、同一行の複数文を整えています。既存の`strict_types`は変更せず、未宣言ファイルへの追加は型変換の挙動確認が必要な別変更とします。

検証: 4ファイルの空白・コメント以外のPHPトークンが変更前と一致し、通常テスト59件と対象PHPの構文検査が成功しました。全ファイルの一括整形は行っていません。

### P2-7: 未使用methodの棚卸し（2026-09-24）

ユーザー確認により将来用途は予定なしとしました。`app`のmethod名を実装・テスト・public・CLI・WordPress連携の参照と照合し、宣言以外の参照がない候補として`FlightCity::airportCandidates()`を確認・削除しました。Factoryのcallable登録、Normalizerテストの変数method呼出、providerのinterface経由の呼出も確認しています。名前の参照数だけで同名methodすべての未使用を証明するものではなく、削除対象は呼出がないと確認できた当該methodに限定しました。

`airportCodes()`は`flightSearchCode()`が利用するため維持します。都市候補検索、都市圏から実空港コードへの変換、国内判定、OTA用コード変換を変更せず、削除済みprovider処理を再度課題にはしていません。通常テスト59件、航空券URL golden 10ケース、変更PHPの構文検査が成功しました。

### P2-6: 未使用ホテルメタデータの削除（2026-09-24）

ユーザー確認により`hotel_place_*`と`property_token`の将来用途は不要としました。ホテルフォームのhidden項目5つとJavaScriptの保存・クリア処理を削除し、目的地候補のNormalizer出力は表示に必要な`name`・`category`・`address`に絞りました。ホテル結果のNormalizerから`property_token`を除去し、実入力fixtureにはフィールドを残して、入力に含まれても出力に保持しない契約を検証します。目的地名による検索、候補の表示・選択・キャッシュ、ホテルの地図用情報は維持します。外部APIの生レスポンスを保存する既存キャッシュの形式・内容は変更しません。

検証: 通常テスト59件、更新したHTML契約12ケース、ブラウザ契約9領域、HTTP応答、変更PHPの構文検査が成功しました。ブラウザでは候補名の選択・手入力・履歴反映と、未使用hidden項目が送信されないことを確認しています。

### P2-5: 未使用の月次制限を削除（2026-09-24）

ユーザー確認によりアプリ内の月次呼び出し回数制限は不要としました。`ApiCache`の全生成箇所で月次制限引数が渡されていないことを確認し、`monthlyLimit`／`usageFile`、`reserveUsage()`と呼出箇所、清掃時の利用カウンターパス専用分岐を削除しました。TTL・JSON容量上限・キーlock・一時file書込・清掃のファイル名制限は維持します。既存ファイルの削除や外部サービスの設定変更は行いません。

検証: 通常テスト59件（キャッシュ・Factory・清掃CLIのテストを含む）と変更PHPの構文検査が成功。実装・テスト内に削除した識別子の参照が残っていないことを確認しました。

### P2-4: キャッシュ保持・容量・清掃（2026-09-24）

ユーザー確認により、期限切れキャッシュは外部API障害時にも利用しません。有効期間は既存の航空券・ホテル1時間、目的地候補15分を維持します。JSONデータはディレクトリごとに既定100 MiB・1,000件までとし、`cache_max_bytes`／`cache_max_entries`で変更できます。保存時に期限切れ・破損データを清掃し、容量不足時は書込日時が古いものから削除します。大きすぎる結果やロック競合時は保存を省略し、取得結果は返します。

`bin/prune-cache.php`は既定dry-run、`--apply`で実削除します。設定ディレクトリ直下の既知のJSON・古い一時ファイルだけを対象にし、再帰削除しません。共通の清掃・書込ロックと非待機のキーlockにより使用中ファイルを保護し、既存のキーlockと一時file経由の書込を維持します。lock自体は競合防止のため削除せず、容量上限の対象外です。ファイル数監視と停止中の保守、毎時実行の登録手順は保守マニュアルに記載しました。本番のcron登録・実データの清掃は未実施です。

検証: 通常テスト59件、HTTP応答、HTML契約12ケース、変更PHPの構文検査が成功。追加6件で期限切れ時の例外伝播、dry-run、安全な対象選別、容量上限、使用中キー・一時ファイルの保護、別プロセスでの同一キー取得の集約、隔離環境のCLI実行を確認しました。

### P2-3: ホテルカードリンク生成の例外境界（2026-09-24）

カード単位のリンク生成失敗は`HotelSearchService::addHotelCardLinks()`で捕捉し、対象カードの`booking_links`だけを空にして後続カードの処理を継続します。ホテル情報・配列のキー・楽天照合による国内カードのリンク制限と名称差し替えは維持します。

`HotelSearchAction`のリンク生成全体を囲む重複catchを削除しました。通常のカード失敗はService内で収まり、Actionは従来どおり結果の有無から`success`／`empty`を決めます。Serviceのカード境界外へ漏れる想定外の処理全体の例外は、Action外側のcatchで`error`になります（従来の内側catchによる握りつぶしは廃止）。楽天リンク取得・照合の失敗と履歴保存の失敗は、既存の独立したcatchで検索を継続します。

検証: `php tests/run.php`は53件成功。追加3件で途中カードの失敗後の継続、古いリンクの除去、楽天照合名の変換失敗と未照合カードの制限、検索全体の失敗時の`error`を確認しました。HTTP応答、HTML契約12ケース、変更PHPの構文検査も成功しています。外部API通信は行っていません。

### P2-2: JSONエンコード失敗時の応答契約（2026-09-24）

`JsonResponse`が`JSON_THROW_ON_ERROR`を共通で付与し、エンコードの`JsonException`を捕捉します。失敗時は元のstatusによらずHTTP 500、`Content-Type: application/json; charset=UTF-8`、固定本文`{"message":"応答データを生成できませんでした。"}`を返します。固定本文は再エンコードせず、ログにはJSONエラーコードのみを記録します。payloadや例外詳細を応答へ含めません。

部分出力・不正UTF-8の無視／置換フラグは無効化し、壊れたデータを成功扱いにしません。正常にエンコードできる応答のstatus・出力形式は維持します。候補検索の外部サービス例外は引き続きActionで処理し、エンコード失敗だけを共通の500応答に統一しました。任意の`JsonSerializable`実装が投げる`JsonException`以外の例外は、この捕捉の対象外です。

検証: `php tests/run.php`は50件成功。不正UTF-8（値・キー）、循環参照、深さ超過、INF／NAN、resource、フラグの違いを確認しました。`php tests/http.php`で実際の500 status・Content-Type・固定本文を検証し、変更PHPの構文検査も成功しています。

### P2-1: Request Validationの共通化（2026-09-24）

`app/Requests/RequestValidation.php`へCSRF一致・文字数範囲・整数範囲・厳密な`Y-m-d`日付の判定を集約し、4つのRequestから利用します。エラー文言・順序、候補検索の早期returnとHTTP status、入力値のtrim・既定値・正規化は各Requestで維持します。航空券の帰着日省略／同日指定、ホテルの翌日以降チェック、フェリーID必須判定などの固有ルールも各Requestに残します。

検証: `php tests/run.php`は48件成功（追加4件でエラー順序、文字数境界、整数の表記・範囲、厳密日付、statusを確認）。`php tests/http.php`、HTML契約12ケース、変更PHPの構文検査も成功しています。

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
