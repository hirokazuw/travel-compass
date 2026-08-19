# Travel Compass

Version 1.6.1.2

**Travel Compass** は、PHP 8 / MySQLで開発した旅行検索Webアプリケーションです。

航空券・ホテルを一つの画面から検索し、複数の旅行予約サービスを比較・利用できるようにしています。

## V1.6.1.2

ホテルの通常検索を国内・海外ともApifyのGoogle Hotels Actorへ統一しました。楽天トラベルAPIは、国内ホテルの楽天トラベル予約ボタン生成にだけ使用します。ホテル検索失敗時に他プロバイダーへはフォールバックしません。

### V1.6.1.2の変更内容

* 国内・海外ホテルの情報取得をApifyへ統一し、共通形式へ正規化
* ホテルの目的地入力から `xtracto/gmaps-suggestion` を呼び出し、都市・駅・地区・観光地・空港・ホテルなどの候補をオートサジェスト表示
* 400msのdebounce、通信キャンセル、15分キャッシュ、最大8件表示、キーボード操作に対応
* Google Placesは目的地候補の補完だけに利用し、ホテル一覧・料金はGoogle Hotels Actorから取得
* 国内・海外ホテルカードの表示デザインを共通化
* ホテル画像の順次フォールバックとプレースホルダー表示に対応
* 公式URLがあるホテルだけ公式サイトボタンを表示
* 国内ホテルはApify結果と楽天トラベルAPIをホテル名で照合し、一致時だけホテル単位の楽天アフィリエイトボタンを表示
* ホテルの国内・海外タブを維持し、予約サイト導線だけを切り替える構造へ整理
* 航空券検索フォームを国内・海外共通にし、空港・都市マスタから予約導線を自動判定
* 航空券・ホテルの人数初期値を1名へ統一
* ヒーロー画像の最大表示幅とスマートフォン表示サイズを縮小

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

Expediaなどの商品情報APIは使用せず、各提携サービスのアフィリエイトリンク変換機能を利用しています。

## 動作環境

* PHP 8
* MySQL
* 自作MVC構成

## セットアップ

1. フォルダをWebサーバーへアップロード
2. `.env.example` を参考に `.env` へDB・API情報を設定
3. `config/config.example.php` を `config/config.php` にコピーし、必要な設定を行う
4. phpMyAdminなどから `database/schema.sql` を実行
5. Webブラウザからアプリケーションへアクセス

## ディレクトリ構成

* Model: `app/Models`
* View: `app/Views`
* Controller: `app/Controllers`
* Service: `app/Services`
* ViewModel: `app/ViewModels`

## セキュリティ

以下の基本的なセキュリティ対策を実装しています。

* CSRF対策
* 入力値検証
* プリペアドステートメント
* HTMLエスケープ
* 環境変数による機密情報の分離

## 公開版

Travel Compassは以下で公開しています。

<https://hirokazu-watabe.jp/travel-compass/>

## SEOファイルの公開

`sitemap.xml` は `https://hirokazu-watabe.jp/travel-compass/sitemap.xml` で公開します。

robots.txtはドメイン直下だけが有効になるため、このリポジトリの `robots.txt` の内容を、サーバーの `https://hirokazu-watabe.jp/robots.txt` に統合してください。
