# 航空券の都市候補

既存の `iata_cities` を利用する入力補助です。DB変更はありません。

## 確認した既存仕様

`database/schema.sql` の列は `id`, `city`, `country`, `iata`, `code_type`, `airports`, `aliases`。英語名は `city`、日本語名などの別名はJSONの `aliases` にあります。収録例は東京（TYO）、ソウル（SEL）です。本番DBの直接確認は行っていません。

従来のフォームは都市名またはコードを直接入力し、Requestの検証後に同じ値を検索・履歴保存へ渡します。Apifyへは既存の `flightSearchCode()` による変換後の値を渡します。都市コードの空港展開（例：TYO → HND,NRT）を含め、この処理は変更していません。

## 入力と検索

- 出発地・目的地は同じJSモジュールで処理します。2文字以上で300ms待って候補を取得します。
- `city` と `iata` の前方一致、`aliases` の部分一致で検索します。プリペアドステートメントとLIKE文字のエスケープを使用し、SQLで最大8件に制限します。
- `search_type=flight_city_suggestions` のPOST endpointを使用し、CSRFを検証します。Apifyには問い合わせません。
- 日本語の別名があれば優先し、なければ `city` を表示します。クリック、上下キーとEnterで選択、Escapeで閉じられます。
- 表示は `東京（TYO）`、hidden値は `TYO`。Requestでコードの形式を検証し、検索値に採用します。再編集・履歴再入力時には古いhidden値を破棄します。
- 未選択時や候補取得失敗時も従来の都市名・コードの直接入力が使えます。
- 履歴の既存 `origin`・`destination` 列へ `iata_cities` の候補と同じ表示文字列（例：東京（TYO））を保存します。表示名称を取得できないコードのみコード表示へフォールバックします。検索サービスには別途IATAコードを渡します。履歴再入力時は末尾のコードを検索値として復元します。既存のコードのみの履歴も再検索できます。過去の保存行の書き換えは行いません。

## 変更ファイル

- サーバー：`app/Models/FlightCity.php`, `app/Actions/FlightCitySuggestionsAction.php`, `app/Core/SearchControllerFactory.php`, `app/Requests/FlightSearchRequest.php`, `app/Actions/FlightSearchAction.php`, `app/ViewModels/FlightSearchViewData.php`
- 表示：`app/Views/search/partials/flight-search-form.php`, `public/assets/app.js`, `public/assets/app.css`, `public/assets/js/flight-suggestions.js`, `public/assets/js/history.js`
- テスト：`tests/flight-suggestions.php`, `tests/run.php`, `tests/browser-tests.js`, `tests/http.php`, `tests/fixtures/search-html.json`
- 文書：本書、`public/assets/js/README.md`

## 検証

PHPテストで日本語・英語・コード検索、SQL特殊文字、CSRF、コード変換、往復・片道、履歴保存を確認します。ChromeのDOMテストで両入力欄の候補選択、キーボード操作、再編集・履歴再入力を確認します。ホテル・フェリーなど既存ブラウザ契約も実行します。HTML契約は候補欄追加に合わせて更新しています。

外部Apifyの実通信・本番MySQLでの疎通は未実施です。ブラウザの候補通信はfixture、モデルのDBテストはSQLiteを使用します。
