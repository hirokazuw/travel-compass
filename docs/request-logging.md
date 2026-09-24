# 検索ログの方針

`SearchController::handle()`ごとにサーバーがランダムな32桁のrequest IDを生成します。外部から渡されたIDは採用しません。同じ検索内のAction・Service・履歴・画面組立の障害と、Response構築完了のログをJSON一行でPHPの`error_log`へ出力します。正常時もresponseイベントを1件記録します。

| 項目 | 意味 |
|---|---|
| `request_id` | 検索処理単位のID |
| `feature` | 固定リストの検索種別。GETは`search_page`、未知のPOSTは`unknown` |
| `event` | `history.read`、`hotel.card_links`などコード内で指定する固定の処理名 |
| `external_service` | `apify`／`rakuten`、外部サービスに関連しない場合はnull |
| `http_status` | 外部APIから取得したHTTP status。通信前・通信失敗で不明ならnull |
| `response_status` | responseイベントに記録するアプリ側HTTP status。HTMLは200、JSONはResponseのstatus、未処理例外は500 |
| `exception_class` | 障害の例外class。responseイベントではnull |

例外メッセージ・stack trace・検索条件・入力・URL・APIレスポンス・token・CSRF・visitor IDは記録しません。`ExternalServiceException`で外部サービスとstatusを明示して渡し、例外メッセージや例外codeからstatusを推測しません。JSON解析失敗では外部HTTPが200でもアプリ側がエラーになる場合があります。

request IDで行を絞り込み、eventで失敗箇所、http_statusで外部APIの状態、response_statusでアプリの返却予定を確認します。responseイベントはResponse構築時点であり、HTML描画・ネットワーク送信完了を保証するアクセスログではありません。ログの時刻・保管・ローテーションはホストのPHPログ設定を利用します。

コンテキストはfinallyで復元し、連続する検索間で共有しません。Controllerを通らないCLI・個別Service呼出・起動失敗・描画失敗は`standalone`としてログ行ごとに別IDになります。入口の最終catchで再記録される未処理例外もstandaloneです。これらと検索IDの関連付け、および送信完了の追跡は対象外です。
