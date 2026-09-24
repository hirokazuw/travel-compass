# Travel Compass SEO診断・改善案

対象：V1.9.3のローカル実装。診断日：2026-09-15。

## 1. 総合評価と確認範囲

SEOの基本設定は整っています。優先する改善は、検索前に読めるサービス説明の追加、現行機能に合ったメタ情報への更新、ヒーロー画像の軽量化です。

本診断はコード・設定例・ローカルの静的ファイルを確認したものです。公開URLはブラウズツールで取得できなかったため、本番のHTTP応答、robots.txtの配置、Googleのインデックス状況、表示速度は未確認です。サイト障害やSEO上のペナルティを確認したという意味ではありません。

診断時点では改善提案のみでした。その後SEO-1をローカル実装済みです。本番配備は未実施で、Gitは操作していません。順位や流入増加を保証するものではありません。

### 対応状況

SEO-1は対応済みです。`app/Views/search/partials/service-guide.php` に「Travel Compassでできること」「使い方」「情報の取得・更新方針」を追加し、検索履歴の下にPHPで出力します。履歴がない場合も検索結果領域の後に表示します。JavaScriptに依存せず、検索前後とも本文を読めます。外部サービスの情報や一時保存による差異、遷移先での最終料金・空席・空室・予約条件確認、フェリー情報が参考情報であることと公式サイトでの運航情報確認を記載しています。公開本文には手動更新の担当・周期を記載しない方針です。初期HTMLの本文を12ケースで確認し、HTTP・ブラウザ8項目の既存契約も通過しました。以下の表は診断時の課題と改善方針を保持しています。

## 2. 現在の良い点

| 項目 | 確認結果 |
|---|---|
| メタ情報 | title・description・canonical・OGPをサーバー側で出力 |
| 構造化データ | `WebSite` と `WebApplication` のJSON-LDを出力 |
| 検索結果の制御 | POST応答はmetaと `X-Robots-Tag` で `noindex, follow` を指定 |
| 広告リンク | 予約サイトへの広告リンクに `rel="sponsored noopener"` を指定 |
| 画像表示 | ヒーロー画像のwidth・heightと `fetchpriority="high"` を指定 |
| サイトマップ | 正規トップURLを記載した `sitemap.xml` が存在 |

広告リンクの `sponsored` 指定はGoogleの推奨に沿っています。[Google：外部リンクの関係を伝える](https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links)

## 3. 優先順位付き改善表

| ID | 優先度 | 現状・課題 | 改善案 | 完了の目安 |
|---|---|---|---|---|
| SEO-1（対応済み） | 高 | 説明本文を追加済み | 検索履歴の下に「できること」「使い方」「情報の取得・更新方針」を配置 | 検索前の初期HTMLに本文が含まれることを確認済み |
| SEO-2（対応済み） | 高 | 非表示H1を削除し、画像下に見えるH1と導入文を配置 | H1は「航空券・ホテル・フェリーを探す」。短い操作案内を添える | 初期HTMLのH1が1つであること、PC・モバイル向けウィンドウ設定のブラウザテストで可視性・横幅・配置順を確認 |
| SEO-3（対応済み） | 高 | title・description・フッター・OGPにフェリー航路検索を反映 | アプリ設定と設定例を更新。OGP・Twitterは同じtitle・descriptionを共用 | 設定とViewへの受け渡し、HTML・HTTP・PC／モバイル向けブラウザ契約を確認 |
| SEO-4 | 高・要確認 | robots.txtの本番配置が不明 | ドメイン直下のrobots.txtとサイトマップ参照を確認 | クロールを誤って禁止しておらず、サイトマップが取得可能 |
| SEO-5 | 中 | ヒーローPNGが約1.53MB | 表示用WebP／AVIF、画面幅別画像を検討しOGP画像と分離 | 画質を保ち転送量を削減。モバイルで改善前後を実測 |
| SEO-6 | 中 | sitemapの `lastmod` が `2026-08-19` | 本番ページの実際の主要更新日に合わせる | 実際の更新と整合。機械的に毎日更新しない |
| SEO-7 | 中 | 制作者名はあるが、フッターに運営情報へのリンクがない | 運営者情報・問い合わせ・プライバシーポリシー・情報の取り扱いへの導線を用意 | 既存ページがあれば活用し、利用者が容易に確認できる |
| SEO-8 | 低 | 構造化データの機能一覧が現行機能と不一致 | フェリー検索、航空券・ホテル履歴等を反映 | 表示内容とJSON-LDが一致し、構文検証が通る |

H1の非表示は、それだけでペナルティという評価ではありません。利用者にとって説明を読みやすくする改善です。キーワードを増やす目的ではなく、具体的で役立つ説明を優先します。[Google：ユーザー第一のコンテンツ](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)

robots.txtはドメイン直下に配置する必要があります。`/travel-compass/robots.txt` だけでは有効になりません。ドメイン全体の既存設定を確認して統合し、他コンテンツのルールを上書きしないようにします。robots.txtがないこと自体でクロールが禁止されるわけではありません。[Google：robots.txtの仕様](https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec)

## 4. トップページの文言案

### title

航空券・ホテル比較とフェリー航路検索｜Travel Compass

### H1

航空券・ホテルの比較と、フェリー航路検索をひとつに。

### description

Travel Compassは、航空券・ホテルの候補と予約サイトへのリンクをまとめて確認できる旅行検索サービスです。都市名・IATAコードでの航空券検索や、港・地図からのフェリー航路検索に対応しています。

### 説明本文に含める内容

- 航空券：都市名やIATAコードで出発地・目的地を選び、日程・人数で検索。
- ホテル：滞在先・宿泊日・人数で候補を確認。
- フェリー：会社・航路または地図から検索。
- 予約は各予約サイト・フェリー会社サイトで行うこと。
- フェリー情報は参考情報であり、最新情報は公式サイトで確認すること。

既存の確定仕様を維持し、子供の年齢入力や新しい子供条件の制約表示は追加しません。「最安値保証」「リアルタイム空席比較」など、実装で保証できない表現は使いません。

## 5. クロール・URL・構造化データの方針

トップの正規URLは `https://hirokazu-watabe.jp/travel-compass/` とし、canonical・サイトマップ・内部リンクのURLを整合させます。HTTP、末尾スラッシュ、index.phpなどの代替URLについては本番の応答を確認してから必要な転送を検討します。[Google：正規URLの指定](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)

検索結果の `noindex, follow` は維持します。SEO目的で検索条件ごとのページを無制限にインデックス可能にする提案ではありません。robots.txtでブロックするとnoindexを読み取れない場合があるため、両者の用途を分けます。[Google：noindexによる登録制御](https://developers.google.com/search/docs/crawling-indexing/block-indexing)

構造化データは実際の画面と機能の説明を正確にする範囲で更新し、架空の評価・価格などは追加しません。今回の診断ではリッチリザルト適格性は検証していません。

## 6. 本番での確認事項

| 手段 | 確認内容 |
|---|---|
| Search ConsoleのURL検査 | トップの登録状況、取得可能性、Googleが選択したcanonical |
| Search Consoleの検索パフォーマンス | 指名／非指名の検索語、表示回数、クリック数、CTR |
| 本番HTTP・HTML確認 | GETトップのstatus・robots・canonical、robots.txtとsitemapの配置・応答 |
| PageSpeed Insights | モバイルのLCP・INP・CLS、画像と外部広告スクリプトの影響 |
| スマートフォンでの目視 | 見出しの読みやすさ、フォームへの到達、画像・説明の配置 |

Core Web Vitalsの目安はLCP 2.5秒以内、INP 200ms未満、CLS 0.1未満です。現時点では実測しておらず、速度不良とは断定しません。実ユーザーデータが不足する場合は、その旨とラボ測定を区別して記録します。[Google：Core Web Vitals](https://developers.google.com/search/docs/appearance/core-web-vitals)

## 7. 実装候補と検証

| 対象ファイル | 変更候補 |
|---|---|
| `config/config.php`・`config/config.example.php` | title・descriptionの更新。環境固有情報は維持 |
| `app/Views/search/index.php` | 見えるH1・説明本文・フッター・OGP説明・画像読込 |
| `app/ViewModels/SeoViewData.php` | 構造化データの機能一覧 |
| `public/assets/app.css`・画像ファイル | 見出しと説明のレイアウト、軽量画像 |
| `sitemap.xml` | 実際の本番更新日に合わせたlastmod |
| ドメイン直下のrobots.txt | 既存サイト全体の設定を確認したうえで必要な調整 |

SEO-1〜SEO-3は対応済みです。次はSEO-5を小さな変更として進め、SEO-4の本番確認を行う方針を推奨します。検索処理・履歴・既存のnoindex契約は維持します。

SEO-3の設定経路：`config/config.php` の `seo.title`・`seo.description` を `SeoViewData` が読み、`app/Views/search/index.php` がtitle・meta description・OGP・Twitterへ出力します。フッター文と画像代替文は同テンプレート内です。画像自体は変更せず、代替文は「Travel Compassの旅行検索サービス」としました。ワークスペース内にはWordPressの読み込みやSEOプラグイン設定は見つかりませんでした。WordPress管理画面・本番サーバー側の重複設定は未確認です。

変更後はPHP・HTTP・ブラウザの既存テストを実行します。HTML契約は意図した変更箇所を確認してから更新し、見出し・メタ情報・canonical・構造化データと検索後のnoindexを確認します。本番配備後にSearch Consoleと速度測定で効果を追跡します。
