# Travel Compass

Version 1.9.3

**Travel Compass** は、PHP 8 / MySQLで開発した旅行検索Webアプリケーションです。

航空券・ホテルを一つの画面から検索し、複数の旅行予約サービスを比較・利用できるようにしています。

## V1.9.3

航空券の出発地・目的地に、既存の`iata_cities`を使うオートコンプリートを追加しました。日本語都市名・英語都市名・IATAコードで検索し、最大8件を表示します。クリックとキーボードで選択できます。

フォーム・検索結果・検索履歴は「東京（TYO）」のように表示し、検索サービスへ渡すIATAコードと履歴へ保存する表示文字列を分離しました。履歴からの再検索にも対応しています。Apifyへの既存の都市圏コード展開・航空券検索処理は維持しています。

航空券とホテル目的地候補の実レスポンス由来fixtureを追加し、Normalizerの出力契約を検証しました。候補件数上限のテストも追加しています。P1-5と別課題のホテル候補検証、ユーザー目視確認によるP0-3の完了を評価表へ反映しました。

配備時は変更した`app/`、`public/assets/app.js`、`public/assets/app.css`、`public/assets/js/`を反映し、環境側の`config/config.php`の`app.version`を`1.9.3`へ更新してください。DB schemaの変更・migrationはありません。提供された元レスポンスJSONは本番配備に不要です。

## V1.9.2

ホテルカードの「公式サイト」を「詳細を見る」へ変更しました。フェリーの条件検索・地図検索の結果付近に運賃・ダイヤ・運航状況の免責文を表示し、料金確認日は「料金確認日：YYYY/MM/DD」に統一しました。確認日が未設定の場合は表示を省略します。

ホテルの子供条件は人数のみ・全員8歳とし、年齢入力や子供条件の制約表示は追加しない仕様に確定しました。フェリー検索履歴は対象外とします。フェリー情報はサイト管理者が3月・9月および変更把握時に手動更新し、休廃止航路は物理削除せず`active = 0`で保持します。

配備時は変更した`app/`と`public/assets/js/ferry-map.js`を反映し、環境側の`config/config.php`の`app.version`を`1.9.2`へ更新してください。V1.9.1より前から更新する場合は、下記V1.9.1の追加ファイルも含めてください。DB schemaの変更・migrationはありません。

## V1.9.1

検索Action、HTML／JSON Response、readonly View Model、領域別Factoryへ処理を整理しました。航空券のURL BuilderとApify Normalizerも領域別に分離し、既存の検索結果・予約リンク・入力エラー表示をテストで保護しています。

フェリー地図の港・地域・座標を検証可能なmasterへ移し、JavaScriptを8つのES moduleへ分割しました。ブラウザのDOM操作テスト、URL・HTML・Normalizerの契約テストを追加し、モジュール単体の更新でもキャッシュが切り替わるようにしています。

配備時は`app/`、`public/assets/app.js`、`public/assets/js/`、`database/ferry-map.json`を含め、環境側の`config/config.php`の`app.version`を`1.9.1`へ更新してください。DB schemaの変更・migrationはありません。航空券・目的地候補の実response fixture補完は継続課題です。

## V1.9.0

現行DBと照合したfresh install用baseline schemaを整備し、航空会社・フェリーを含む全master dataを一括構築できるようにしました。検索履歴のDB障害は航空券・ホテル検索および画面描画から分離し、補助機能の障害時も主要機能を継続します。

環境固有の`config.php`とSFTP設定をGit管理から分離し、未使用だったTrip.com、Booking.com、ena、さくらトラベルの処理を削除しました。また、PHP単体test、SQLiteによるModel契約test、MySQL baseline integration test、GitHub Actionsを追加しました。

## V1.8.1

ホテル検索を国内、韓国、その他海外の3区分に整理しました。韓国ホテルではNOL WORLD、その他海外ホテルではExpediaへの検索条件付き導線を一覧上部に表示し、各区分に合わせてホテルカードの予約サイトボタンを調整しました。

## V1.8.0

フェリー検索機能を追加しました。フェリー会社と航路による条件検索に加え、日本地図から地域、A地点、B地点の順に航路を探せます。地図上では港と航路線をプレビューでき、運航会社、航路名、所要時間、参考運賃、車両積載可否、夜行便情報をまとめて表示します。航路カードは、航路またはフェリー会社に登録された公式予約・公式サイトへ接続します。

## V1.7.1

航空券検索結果をIATAコード単位で集約し、同じ航空会社の便を1枚のカードにまとめて表示するようにしました。航空会社マスタから正式名称、アライアンス、FFP、公式サイトを取得し、公式URLが登録されている場合はカードのメイン領域から航空会社サイトを開けます。既存のOTA予約導線は引き続き利用できます。

## V1.7.0.0

航空券・ホテルの検索履歴を検索種別ごとのテーブルへ分離し、両方の履歴をまとめて再利用できるようにしました。また、検索条件と処理状況を表示するローディングUIを追加しました。

### 国内・海外航空券検索

* 都市名・別名・IATAコードから出発地と目的地を解決
* 複数空港を持つ都市では、`iata_cities.airports` に登録された空港を検索対象として使用
* 航空券一覧・参考価格はApifyのGoogle Flights Actorから取得
* Apifyで取得できない場合、他の航空券検索APIへフォールバックしない
* Aviasales CDNから航空会社ロゴを表示し、取得できない場合はIATAコードを表示
* Apifyの航空券検索結果をキャッシュ
* 国内・海外航空券の検索履歴を表示

参考価格はリアルタイム運賃ではありません。実際の料金、空席状況および予約条件は各OTAで確認します。

#### 航空券のOTA予約導線

国内航空券：

* Expedia
* Agoda
* エアトリ
* トラベリスト
* リアルチケット

海外航空券：

* Expedia
* Agoda
* JTB
* SkyTicket
* エアトリ（Skygate）

### ホテル検索

* 国内・海外ともApifyの `johnvc/google-hotels-search-scraper` を使用して共通ホテルカードを生成
* ホテル名、写真、星クラス、評価、口コミ、設備、1泊料金、合計料金、チェックイン・アウトを表示
* Actorレスポンスをサーバー側で共通形式へ正規化し、トークンをブラウザへ公開しない
* 同一条件の検索結果をキャッシュし、Actorの実行回数を抑制
* 検索範囲に応じて国内向け・海外向けOTAの予約導線だけを切り替え
* ホテルは表記揺れによる誤判定を避けるため国内・海外タブを維持し、選択したタブで予約導線だけを切り替え
* 航空券は検索フォームを国内外共通にし、空港・都市マスタから予約導線を自動判定

#### ホテルのOTA予約導線

* じゃらん
* Yahoo!トラベル
* 一休.com
* Expedia

国内ホテルでは、Apifyのホテル名と楽天トラベルAPIのホテル名が安全に一致した場合に限り、各ホテルカードへ「楽天トラベルで予約」ボタンを表示します。

### Apify Actorによる通常検索

* 国内・海外航空券はApifyのGoogle Flights Actorだけを検索・価格取得元として使用
* 海外ホテルも国内ホテルと同じApifyのGoogle Hotels Actorを使用
* 海外ホテル検索からExpedia・Hotels.com・JTB（バリューコマース）へ遷移
* 同一条件のレスポンスを1時間キャッシュし、APIエラー時も画面全体の表示を維持
* 海外ホテルのAPIエラー時は結果領域に案内を表示し、画面全体の表示を維持
* `APIFY_TOKEN` はサーバー側の環境変数から読み込み、ブラウザへ出力しない
* 航空券検索ではScrape.do、SerpAPI、Travelpayoutsを使用しない
* 楽天トラベルAPIは国内ホテルの楽天トラベル予約ボタン生成にだけ使用
* Aviasalesは航空会社ロゴのCDN取得にだけ使用

### その他

* APIキーやトークンを環境変数で管理
* APIエラー時も検索画面を壊さないフォールバック処理
* SEO・OGP・Twitter Card・構造化データ対応
* Apify通信、航空券検索、ホテル検索、目的地候補検索、レスポンス正規化を個別サービスへ分離
* 航空券とホテルの外部予約URL生成を専用Builderへ分離

Expediaなどの商品情報APIは使用せず、各提携サービスのアフィリエイトリンク変換機能を利用しています。

## 動作環境

* PHP 8
* MySQL
* 自作MVC構成

## セットアップ

1. フォルダをWebサーバーへアップロード
2. `.env.example` を参考に `.env` へDB・API情報を設定
3. `config/config.example.php` をGit管理外の `config/config.php` にコピーし、必要な環境固有設定を行う
4. 新規DBではphpMyAdminなどから `database/schema.sql` を実行（今後の既存DB更新は `database/migrations/` 直下の未適用SQLを使用。`archive/`は適用済み）
5. Webブラウザからアプリケーションへアクセス

## ディレクトリ構成

* Model: `app/Models`
* View: `app/Views`
* Controller: `app/Controllers`
* 領域別の依存構築: `app/Factories`（入口は`app/Core/SearchControllerFactory.php`）
* 検索Action: `app/Actions`
* HTML／JSON Response: `app/Http`
* Service: `app/Services`
* 領域別response変換: `app/Services/Normalizers`
* フェリー地図master: `database/ferry-map.json`（仕様・更新手順は`database/ferry-map.md`）
* ViewModel: `app/ViewModels`

## テスト

外部APIや本番設定を使わない自動テストは、次のコマンドで実行します。

```bash
php tests/run.php
php tests/http.php
php tests/view-contract.php
php tests/flight-url-golden.php
node tests/browser.mjs
```

Request、response正規化、URL生成、IATA、航空会社集約、ホテル名寄せ、フェリーModel／Service、baseline構成を検査します。GitHub ActionsではPHP構文検査に加え、MySQL 8の空DBへ`database/schema.sql`を適用するintegration testも実行します。

検索Actionのstatus・履歴障害時の継続も検査します。`tests/http.php`はローカルのPHP組込サーバーを起動し、GET／POSTのactive tab、validation error、HTTP status、`X-Robots-Tag`、JSONとCSRF更新の契約を確認します。PHPのPDO SQLite・mbstringと`proc_open`が必要です。

Controllerの単体テストではcallable Actionと`SearchPageBuilderInterface`のfakeを注入し、`handle()`が返すResponseを検証します。Factoryのリクエスト内依存共有と領域別cache設定も通常テストに含みます。

Normalizerの入力fixtureと期待出力は`tests/fixtures/normalizers/`にあります。通常テストで領域別クラスと旧互換窓口のkey・値・型・順序を照合します。ホテル・航空券・ホテル目的地候補は匿名化した実response由来fixtureで検証し、合成fixtureも境界条件の検証用に維持しています。

`tests/view-contract.php`は12ケースの描画結果をP1-2変更前のHTML契約と照合します。asset version・年・改行コード以外は一致が必要です。意図的にHTMLを変更した場合だけ、出力の差分を確認してから`php tests/view-contract.php --record`で契約を更新してください。Viewはreadonlyの`SearchPageViewModel`を受け取り、partialにも明示的に渡します。

`tests/flight-url-golden.php`はprovider別Builder分離前のURLを10ケースで完全比較します。SkyGateのUUIDだけ正規化し、形式と重複も検査します。意図的なURL仕様変更時だけ、`tests/fixtures/flight-urls.json`の差分を確認して`--record`で更新してください。provider固有の変更先は`app/Services/FlightUrls/`、選択・順序は`FlightUrlBuilder`です。

ブラウザテストはNode.js 24とChrome／Chromiumを使い、npm installは不要です。自動検出されない場合は`BROWSER_BINARY`に実行ファイルを指定します（PHPは必要に応じて`PHP_BINARY`）。実際のViewをローカルHTTPで配信し、APIをfakeにして7領域のDOM操作を検証します。ブラウザは一時profileで起動し終了後に削除します。

フロントエンドは`public/assets/app.js`から`public/assets/js/`のES moduleを初期化します。配備時は両方を含めてください。ビルドは不要で、全JSの内容hashがキャッシュversionになります。機能とselectorの対応は`public/assets/js/README.md`を参照してください。

## セキュリティ

以下の基本的なセキュリティ対策を実装しています。

* CSRF対策
* 入力値検証
* プリペアドステートメント
* HTMLエスケープ
* 環境変数による機密情報の分離
* 匿名Cookie IDによる検索履歴の利用者分離

## 公開版

Travel Compassは以下で公開しています。

<https://hirokazu-watabe.jp/travel-compass/>

## SEOファイルの公開

`sitemap.xml` は `https://hirokazu-watabe.jp/travel-compass/sitemap.xml` で公開します。

robots.txtはドメイン直下だけが有効になるため、このリポジトリの `robots.txt` の内容を、サーバーの `https://hirokazu-watabe.jp/robots.txt` に統合してください。
