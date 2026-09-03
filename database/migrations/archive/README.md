# Archived migrations

このディレクトリのSQLは既存DBへ適用済みで、現在の内容は`database/schema.sql`へ統合されています。

- `20260819_add_visitor_id_to_search_history.sql`: 検索履歴の利用者分離
- `20260822_fix_airlines.sql`: 航空会社masterの現行化
- `20260822_fix_ferries.sql`: フェリー会社・航路masterの現行化

新規DBには`database/schema.sql`だけを適用してください。archive内のSQLを追加適用する必要はありません。

archive内のSQLは、過去のDBを更新した手順の確認と監査のために保持しています。現在のDBへ再適用しないでください。今後作成する未適用migrationは`database/migrations/`直下へ配置します。
