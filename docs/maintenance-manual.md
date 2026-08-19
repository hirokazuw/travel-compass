# Travel Compass 作者向け保守マニュアル

調査基準: `master` / `1fc8393d5e0cb7bb9e6716b751b1ea16f155eb33`

## 1. システム構成

PHP 8／MySQL製の単一画面型自作MVCです。専用RouterやDIコンテナはなく、`public/index.php`が起動と依存構築、`SearchController`がHTTP処理を振り分けます。

```text
Browser → index.php → public/index.php
→ SearchController::index()
→ Request → Model / Service → View → app.js
```

| 場所 | 役割 |
|---|---|
| `index.php` | Webルート入口。`public/index.php`を読む |
| `public/index.php` | Session、autoload、Env、設定、PDO、全依存生成 |
| `app/Controllers/SearchController.php` | GET、航空券、ホテル、候補検索の統括 |
| `app/Requests/` | POST値、CSRF、日付、人数の検証 |
| `app/Models/SearchHistory.php` | 履歴保存・統合取得 |
| `app/Models/FlightCity.php` | 都市、IATA、都市圏空港、国内判定 |
| `app/Services/` | 外部API、cache、変換、OTA URL |
| `app/ViewModels/` | status文言、SEO |
| `app/Views/search/` | Form、結果、履歴、loading |
| `public/assets/app.js` | tab、候補、loading、もっと見る、履歴再入力 |
| `config/config.php` / `.env` | DB、API、SEO設定 |
| `database/schema.sql` | 履歴DDL。IATA DDLは含まない |

### ルーティング

| 条件 | メソッド |
|---|---|
| GET | 初期画面 |
| POST `search_type=flight` | `handleFlightSearch()` |
| POST `search_type=hotel` | `handleHotelSearch()` |
| POST `hotel_destination_suggestions` | `destinationSuggestions()` |

## 2. 航空券検索

```text
flight-search-form.php → app.js → POST
→ SearchController::handleFlightSearch()
→ FlightSearchRequest::fromPost()
→ SearchHistory::createFlight()
→ FlightCity::isDomestic() / flightSearchCode()
→ FlightSearchService::search()
→ ApifyFlightSearch → ApiCache → ApifyClient
→ ApifyResponseNormalizer::normalizeFlights()
→ FlightUrlBuilder::buildFlightLinks()
→ flight-results.php → app.js
```

- 入力: `origin`, `destination`, `departure_date`, `return_date`, `travelers`
- 検証: CSRF、地名必須・100文字、厳密な日付、帰着順序、人数1～9
- 履歴: Validation成功直後、Apifyより前に`flight_searches`へ保存
- IATA: `iata_cities`の`city`, `iata`, JSON `aliases`。都市圏は`airports`を展開
- 国内: 出発地・目的地の両方が`FlightCity::isDomestic()`で国内
- Apify入力: 出発／到着コード、日付、大人、economy、ja/jp、JPY、最大page数
- 変換: `best_flights`, `other_flights`, `all_flights`をカード形式へ変換し価格昇順
- OTA: 共通Expedia／Agoda、国内エアトリ／トラベリスト／リアルチケット、海外JTB／SkyTicket
- status: `not_configured`, `unsupported_route`, `empty`, `error`
- 別航空券APIへのfallbackはない。Apify失敗時もOTA導線は生成される

## 3. ホテル検索

```text
search-panel.php → app.js → POST
→ SearchController::handleHotelSearch()
→ HotelSearchRequest::fromPost()
→ SearchHistory::createHotel()
→ HotelSearchService → ApifyHotelSearch
→ ApiCache → ApifyClient → normalizeHotels()
→ 国内のみRakutenTravelService::searchAffiliateLinks()
→ HotelSearchService::matchRakutenHotels()
→ addHotelCardLinks() → HotelUrlBuilder
→ hotel-results.php → app.js
```

- 検証: CSRF、目的地、厳密な日付、checkout順序、大人1～9、子供0～9
- 国内外: 目的地から自動判定せず、選択tabの`hotel_scope`
- 履歴: Apifyより前に`hotel_searches`へ保存。`guests`は大人＋子供
- Apify入力: 目的地、check-in/out、大人、子供、ja/jp、JPY。子供年齢は全員8歳
- 変換: 名前、説明、link、住所、座標、class、評価、口コミ、料金、時刻、画像、設備
- `official_url`: Actorの`link`。公式ドメインかは検証していない
- 楽天: 国内だけ。完全一致または8文字以上の一意な部分一致で楽天ボタンを付与
- 国内OTA: 楽天、じゃらん、Yahoo!トラベル、一休、Expedia
- 海外OTA: Expedia、Hotels.com
- 楽天例外はlogのみ。Apifyホテルカードは継続

### 目的地候補

`app.js`が400ms debounce後に同じURLへfetchし、`destinationSuggestions()`→`DestinationSuggestionRequest`→`ApifyDestinationSearch`を実行します。候補障害時も手入力可能です。

## 4. データベース

| Table | 用途 | 書込 | 読込 |
|---|---|---|---|
| `flight_searches` | 航空券履歴 | `createFlight()` | `recent()`（`visitor_id`で絞込） |
| `hotel_searches` | ホテル履歴 | `createHotel()` | `recent()`（`visitor_id`で絞込） |
| `iata_cities` | IATA・都市圏・国内判定 | 現行アプリなし | `FlightCity` |
| `travel_searches` | 旧履歴 | なし | なし |

初回アクセス時に256-bitの匿名`visitor_id`をCookieへ発行します。Cookieには検索条件を保存せず、90日有効、`HttpOnly`、`SameSite=Lax`、HTTPS通信時は`Secure`です。

`recent($visitorId, 6)`は現在の匿名利用者に一致する航空券とホテルを各6件取得し、PHPで統合・再sortして全体6件にします。別のCookie IDの履歴は取得しません。`iata_cities`のDDL・Index・初期dataは`database/schema.sql`に収録しています。

## 5. 外部サービス

| Service | 用途 | 呼出元 | 障害時／代替 |
|---|---|---|---|
| Apify Google Flights | 便・参考価格 | `ApifyFlightSearch` | 便なし、別APIなし |
| Apify Google Hotels | ホテルカード | `ApifyHotelSearch` | ホテル結果なし |
| Apify Maps Suggestion | 目的地候補 | `ApifyDestinationSearch` | 手入力 |
| 楽天トラベル | 国内楽天link | `RakutenTravelService` | 楽天buttonのみ欠落 |
| Aviasales CDN | 航空会社logo | `flight-results.php` | IATA表示 |
| A8 / ValueCommerce | affiliate変換 | `Views/search/index.php` | 通常URL |

環境変数は`DB_DSN`, `DB_USER`, `DB_PASSWORD`, `APIFY_TOKEN`, `RAKUTEN_APPLICATION_ID`, `RAKUTEN_ACCESS_KEY`, `RAKUTEN_AFFILIATE_ID`, `RAKUTEN_REFERER`です。

Scrape.do、SerpAPI、Travelpayoutsの実装はありません。残骸候補は`travel_searches`、`FlightUrlBuilder::sakura()`、非表示Trip.com／Booking.com／ena、未使用`affiliate.hotel_url`、`hotel_place_*`です。

## 6. Frontend

PHPはForm、CSRF、Validation、API、DB、全カードHTMLを担当します。JavaScriptはtab、片道、候補fetch、二重送信防止、擬似loading、画像fallback、もっと見る、履歴再入力を担当します。「もっと見る」は追加通信ではなく既存HTMLのhidden解除です。

## 7. SEO

| 項目 | 制御 |
|---|---|
| title / description / canonical | `config.php` → `SeoViewData` → View |
| OGP / Twitter | `Views/search/index.php` |
| JSON-LD | `SeoViewData::create()` |
| sitemap / robots | ルート静的ファイル |
| index/noindex | `SearchController`, `SeoViewData` |

GETは`index, follow`、POSTはmetaとX-Robots-Tagで`noindex, follow`です。

## 8. 障害対応

| 症状 | 最初に見る | 次に見る |
|---|---|---|
| 航空券不可 | `FlightSearchService::search()`、PHP log | `FlightCity`、Apify設定 |
| ホテル不可 | `handleHotelSearch()` | `ApifyHotelSearch`、cache権限 |
| Apify error | `ApifyClient::run()` | token、Actor URL、HTTP body |
| 楽天 error | `RakutenTravelService::request()` | `RAKUTEN_*`、Referer、名寄せ |
| 履歴未保存 | `SearchHistory` | PDO、table、列、権限 |
| OTA異常 | URL Builder | View、affiliate JS |
| IATA異常 | `FlightCity::find()` | aliases、airports、国列 |
| loading残留 | `app.js`の`pageshow()` | outcome、JS例外 |
| 結果非表示 | 対応results View | status、Controller返却値 |
| 楽天だけ非表示 | `isAffiliateConfigured()` | `matchRakutenHotels()` |

初動順は、PHP log → 設定 → status → Service → 外部API → DB/cache → View → Browser Consoleです。履歴INSERT失敗は検索も停止し、楽天障害は画面に出ずlogだけに残ります。
