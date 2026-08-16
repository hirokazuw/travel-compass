# Trip Search MVC

PHP 8 / MySQLで動く独立MVCアプリです。Trip.comの商品情報APIは使用せず、公式アフィリエイトリンクへ送客します。

1. フォルダをConoHaの `hirokazu-watabe.jp/travel` にアップロード
2. `config/config.example.php` を `config/config.php` にコピー
3. DB情報と、Trip.com管理画面で生成したホテル・航空券リンクを設定（URLは改変せず貼り付ける）
4. phpMyAdminで `database/schema.sql` を実行
5. `https://hirokazu-watabe.jp/travel/` を表示

Modelは `app/Models`、Viewは `app/Views`、Controllerは `app/Controllers` に分離しています。CSRF対策、入力検証、プリペアドステートメント、HTMLエスケープを実装済みです。
