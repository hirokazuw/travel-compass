# Travel Compass

Version 1.5.0

**Travel Compass** は、PHP 8 / MySQLで開発した旅行検索Webアプリケーションです。

航空券・ホテルを一つの画面から検索し、複数の旅行予約サービスを比較・利用できるようにしています。

## V1.5.0

V1.5.0では、国内・海外航空券検索と国内ホテル検索を完成させ、検索結果から各OTAへ移動できる予約導線を整理しました。

### 国内・海外航空券検索

* 都市名・別名・IATAコードから出発地と目的地を解決
* 複数空港を持つ都市では、`iata_cities.airports` に登録された空港を検索対象として使用
* AeroDataBox Routes APIから直行便の就航航空会社を取得
* 航空会社コードを`airlines.iata_code`と照合し、日本語名を優先して表示
* Aviasales Data APIの価格傾向データを航空会社別の参考価格として表示
* Aviasalesで価格を取得できない場合は、既存のSerpAPI処理へフォールバック
* Aviasales CDNから航空会社ロゴを表示し、取得できない場合はIATAコードを表示
* AeroDataBoxのレスポンスを出発空港単位で24時間キャッシュ
* AviasalesおよびSerpAPIの価格データをキャッシュ
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

### 国内ホテル検索

* 楽天トラベルAPIを使用してホテルカードを生成
* ホテル名、写真、所在地、評価、アクセス情報、設備、参考価格を表示
* 楽天トラベル掲載価格を参考価格として表示
* 検索したホテル名を各OTAの検索URLへ引き継ぐ予約導線を提供

#### ホテルのOTA予約導線

* 楽天トラベル
* じゃらん
* Yahoo!トラベル
* 一休.com
* Expedia

海外ホテル検索は開発中です。

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
