# Travel Compass

Version 1.2.0

**Travel Compass** は、PHP 8 / MySQLで開発した旅行検索Webアプリケーションです。

航空券・ホテルを一つの画面から検索し、複数の旅行予約サービスを比較・利用できるようにしています。

## 主な機能

* 航空券検索（SerpApi / Google Flights）
* ホテル検索（SerpApi / Google Hotels）
* 楽天トラベル検索
* 都市名・別名・IATAコードによる空港コード解決
* Trip.com、Booking.com、Expedia、Agoda、enaへの航空券検索URL生成
* 国内線向けのさくらトラベル、エアトリ検索URL生成
* 複数のホテル予約サイトへの検索URL生成
* 最近の航空券検索履歴
* SEO・OGP・Twitter Card・構造化データ対応

Trip.comおよびExpediaの商品情報APIは使用せず、公式アフィリエイトリンクを利用しています。

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
