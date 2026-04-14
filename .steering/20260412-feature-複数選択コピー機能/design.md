# 設計書：複数選択コピー機能

## アーキテクチャ概要

```
┌─────────────────────────────────────────────────────────────┐
│                     React Frontend (Vite)                   │
├──────────────────────┬──────────────────────────────────────┤
│                      │                                      │
│  CalendarView        │  RightPanel                          │
│  (カレンダービュー)   │  (右パネル)                           │
│                      │                                      │
│  - Date clicked      │  - TransactionList                  │
│    → Fetch contents  │    ├─ Checkbox rendering           │
│                      │    ├─ Selection counter             │
│                      │    └─ Copy button                   │
│                      │                                      │
│                      │  - CopyModal                        │
│                      │    ├─ DatePicker                    │
│                      │    └─ Copy execution                │
└──────────────────────┴──────────────────────────────────────┘
                              ↕ HTTP
┌─────────────────────────────────────────────────────────────┐
│                  Laravel Backend (API)                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  GET /api/getTransactions?recorded_at={date}               │
│  POST /api/copyMultipleContents                            │
│                                                              │
│  ContentController                                          │
│  ├─ copyMultipleContents()                                 │
│  │  ├─ Validation                                          │
│  │  ├─ Authorization check                                 │
│  │  └─ Transaction (batch insert)                          │
│  │                                                          │
│  └─ getTransactions()                                      │
│     └─ Filter by user_id & recorded_at                     │
│                                                              │
│  Content Model                                             │
│  └─ Database queries                                       │
└─────────────────────────────────────────────────────────────┘
```

## コンポーネント設計

### 1. TransactionList コンポーネント

**責務**:
- 指定された日付の支出一覧を表示
- 各支出にチェックボックスを表示
- 選択状況をリアルタイムに更新
- 「コピー」ボタンを表示

**実装の要点**:
- Material-UI Checkbox を使用
- React Hook Form で選択状態を管理（ローカル状態）
- チェック/アンチェックで親コンポーネントへ選択状態を通知
- 選択数に応じて「選択: X件」を表示

**ファイル**: `src/resources/js/Components/TransactionList.tsx`

```typescript
interface TransactionListProps {
  transactions: Transaction[];
  onSelectionChange: (selectedIds: number[]) => void;
  onCopyClick: (selectedIds: number[]) => void;
}

export const TransactionList = ({ transactions, onSelectionChange, onCopyClick }: TransactionListProps) => {
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  
  const handleCheckChange = (id: number) => {
    const updated = selectedIds.includes(id)
      ? selectedIds.filter(sid => sid !== id)
      : [...selectedIds, id];
    setSelectedIds(updated);
    onSelectionChange(updated);
  };
  
  return (
    <>
      {transactions.map(tx => (
        <Box key={tx.id}>
          <Checkbox
            checked={selectedIds.includes(tx.id)}
            onChange={() => handleCheckChange(tx.id)}
          />
          {/* transaction details */}
        </Box>
      ))}
      <Typography>選択: {selectedIds.length}件</Typography>
      <Button onClick={() => onCopyClick(selectedIds)}>コピー</Button>
    </>
  );
};
```

### 2. CopyModal コンポーネント

**責務**:
- モーダルダイアログでコピー先日付を入力
- 日付ピッカーを提供
- バリデーション（同じ日付チェック）
- コピー API を呼び出し

**実装の要点**:
- Material-UI Dialog を使用
- Material-UI DatePicker または TextField + Calendar popup
- リクエスト実行中は「コピー」ボタンを disable
- API レスポンス後、成功メッセージまたはエラーメッセージを表示

**ファイル**: `src/resources/js/Components/CopyModal.tsx`

```typescript
interface CopyModalProps {
  open: boolean;
  sourceDate: string;
  selectedIds: number[];
  onClose: () => void;
  onCopySuccess: () => void;
}

export const CopyModal = ({ open, sourceDate, selectedIds, onClose, onCopySuccess }: CopyModalProps) => {
  const [destinationDate, setDestinationDate] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const handleCopy = async () => {
    // validation: sourceDate !== destinationDate
    // API call
    // success/error handling
  };
  
  return (
    <Dialog open={open} onClose={onClose}>
      <DialogTitle>コピー先の日付を選択</DialogTitle>
      <DialogContent>
        <TextField
          type="date"
          value={destinationDate}
          onChange={(e) => setDestinationDate(e.target.value)}
        />
        {error && <Alert severity="error">{error}</Alert>}
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>キャンセル</Button>
        <Button onClick={handleCopy} disabled={loading} variant="contained">
          {loading ? '処理中...' : 'コピー'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};
```

### 3. ContentController（バックエンド）

**責務**:
- 複数支出の一括コピー処理
- 入力値のバリデーション
- 権限チェック
- トランザクション処理で一括挿入

**実装の要点**:
- リクエスト検証クラス: `CopyMultipleContentsRequest`
- 同じ日付へのコピーはエラー（`APIBusinessLogicException`）
- トランザクション内で全コンテンツをループして新規作成
- 新規作成された ID 一覧をレスポンスに含める

**ファイル**: `src/app/Http/Controllers/ContentController.php`

```php
public function copyMultipleContents(CopyMultipleContentsRequest $request)
{
    $validated = $request->validated();
    
    // Validation: source_date !== destination_date
    if ($validated['source_date'] === $validated['destination_date']) {
        throw new APIBusinessLogicException('コピー先の日付は異なる日付を選択してください');
    }
    
    // Fetch source contents
    $sourceContents = Content::where('user_id', auth()->id())
        ->where('recorded_at', $validated['source_date'])
        ->whereIn('id', $validated['content_ids'])
        ->get();
    
    // Transaction: copy all contents
    $createdIds = [];
    DB::transaction(function () use ($sourceContents, $validated, &$createdIds) {
        foreach ($sourceContents as $content) {
            $newContent = $content->replicate();
            $newContent->recorded_at = $validated['destination_date'];
            $newContent->save();
            $createdIds[] = $newContent->id;
        }
    });
    
    return response()->json([
        'success' => true,
        'message' => count($createdIds) . '件の支出をコピーしました',
        'copied_count' => count($createdIds),
        'created_ids' => $createdIds,
    ]);
}
```

## データフロー

### ユースケース：複数支出をコピー

```
1. ユーザーがカレンダーの日付「14」をクリック
   → GET /api/getTransactions?recorded_at=2026-03-14
   → 該当日付の全支出を取得
   → TransactionList コンポーネントが支出一覧 + チェックボックスを表示

2. ユーザーがチェックボックスを操作
   → TransactionList で選択状態（selectedIds）を更新
   → 親コンポーネントが選択状態を管理
   → 「選択: X件」を更新

3. ユーザーが「コピー」ボタンをクリック
   → CopyModal がオープン
   → ユーザーがコピー先日付を入力

4. ユーザーが「コピー」ボタン（モーダル内）をクリック
   → POST /api/copyMultipleContents
   {
     "source_date": "2026-03-14",
     "destination_date": "2026-04-14",
     "content_ids": [1, 3, 5]
   }
   → バックエンドで複数コピー処理実行
   → 新規コンテンツ作成
   → 完了メッセージを返す

5. フロントエンドが成功メッセージを表示
   → モーダルをクローズ
   → カレンダーを再読み込み（2026-04-14 のデータを取得）
   → 新しい支出がカレンダーに表示
```

## エラーハンドリング戦略

### カスタムエラークラス

既存の `APIBusinessLogicException` を使用：

```php
throw new APIBusinessLogicException('エラーメッセージ');
```

### エラーハンドリングパターン

| エラー | HTTP ステータス | メッセージ | 対処 |
|--------|-----------------|-----------|------|
| 同じ日付へのコピー | 400 | 「異なる日付を選択してください」 | ユーザーが日付を修正 |
| 権限外のコンテンツ | 403 | 「権限がありません」 | ユーザーに通知 |
| 存在しないコンテンツ | 404 | 「支出が見つかりません」 | ページ再読み込み |
| 無効な日付フォーマット | 422 | バリデーションエラー | フロント側で事前チェック |

## テスト戦略

### ユニットテスト（バックエンド）

- [ ] 複数支出をコピーできる
- [ ] 同じ日付へのコピーでエラーが返される
- [ ] 権限外のコンテンツはコピーされない
- [ ] トランザクション成功時に all 挿入される
- [ ] トランザクション失敗時に rollback される

### 統合テスト（フロントエンド + バックエンド）

- [ ] チェックボックスの選択・解除が動作する
- [ ] 「選択: X件」が正確に更新される
- [ ] モーダルダイアログの表示・非表示が動作する
- [ ] 日付ピッカーから日付を選択できる
- [ ] コピー API が正常に実行される
- [ ] エラーメッセージが表示される

## セキュリティ考慮事項

- **認可チェック**: `auth()->id()` で current user を特定し、権限チェック
- **入力値バリデーション**: `CopyMultipleContentsRequest` で content_ids が current user に属するか確認
- **SQL インジェクション**: Eloquent ORM を使用（パラメータバインディング）
- **日付フォーマット**: YYYY-MM-DD 形式で統一、バリデーション

## パフォーマンス考慮事項

- コピー対象が大量（例: 1000件以上）の場合、バッチ挿入を検討
- 現状は `->replicate()` + ループで対応（一般的な用途では問題ない）
- 必要に応じて `DB::table()->insert($batch)` に変更可能

## 将来の拡張性

- 複数選択コピー履歴の記録（audit log）
- 定期実行（複数選択をテンプレート化）
- CSV インポート・エクスポート時の複数コピー機能の活用
