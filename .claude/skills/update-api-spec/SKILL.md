---
name: update-api-spec
description: OpenAPI仕様を自動更新。エンドポイント、スキーマ、パラメータを実装に基づいて追加。
allowed-tools: Read, Write, Edit, Glob, Grep
---

# API仕様更新スキル

実装した機能に基づいて、OpenAPI仕様を自動更新するためのスキルです。

## 前提条件

### 必須ファイル

以下のファイルが存在する必要があります：

- `api/openapi.yaml` - メインのOpenAPI仕様ファイル
- `api/schemas/` - スキーマ定義ディレクトリ
- `api/responses/common.yaml` - 共通応答定義
- `api/parameters/common.yaml` - 共通パラメータ定義

### 既存仕様の優先順位

`api/openapi.yaml` に既存の仕様がある場合：

1. **既存のOpenAPI仕様** - 最優先
   - プロジェクトの実装に基づいている
   - このスキルのテンプレートより優先する

2. **このスキルのテンプレート** - 参考資料
   - 新しいエンドポイント追加時の参考
   - 既存パターンに合わせて調整

## 更新対象

このスキルで更新できる項目：

### 1. 新しいエンドポイント追加
- `api/openapi.yaml` の `paths` セクションに追加

### 2. スキーマ定義追加
- `api/schemas/` 配下の適切なファイルに追加
- または新規スキーマファイル作成

### 3. パラメータ定義追加
- `api/parameters/common.yaml` に再利用可能な定義を追加

### 4. 応答定義追加
- `api/parameters/common.yaml` に再利用可能な定義を追加

## 実装の手順

このスキルの詳細な使用方法は以下を参照してください：

- **テンプレート**: `./template.md`
- **詳細ガイド**: `./guide.md`

## 出力先

更新内容は以下に保存されます：

```
api/openapi.yaml                # メイン仕様ファイル
api/schemas/[domain].yaml       # スキーマファイル
api/parameters/common.yaml      # パラメータ定義
api/responses/common.yaml       # 応答定義
```

## 使用例

新しい取引レポート機能を実装した場合：

```
実装内容:
- GET /reports/monthly
- GET /reports/yearly
- POST /reports/export

API仕様更新:
- paths に新エンドポイント追加
- schemas/transaction.yaml に Report スキーマ追加
- parameters/common.yaml に ReportTypeParameter 追加
- responses/common.yaml に ReportSuccess 応答追加
```

## 注意事項

- ✅ 既存エンドポイントの仕様に合わせて新規追加
- ✅ セキュリティ定義（BearerAuth など）を忘れずに
- ✅ 参照パス（`$ref`）の相対パスを正しく指定
- ❌ 手動で実装したコードを修正しない
- ❌ 既存エンドポイントの仕様を変更しない（互換性維持）
