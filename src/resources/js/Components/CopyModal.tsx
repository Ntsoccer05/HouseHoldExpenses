import React, { useState } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  TextField,
  Alert,
  CircularProgress,
} from '@mui/material';
import { copyMultipleContents } from '../api/transactionApi';

interface CopyModalProps {
  open: boolean;
  sourceDate: string;
  selectedIds: number[];
  onClose: () => void;
  onCopySuccess: () => void;
}

/**
 * 複数支出コピー用モーダルダイアログ
 */
export const CopyModal: React.FC<CopyModalProps> = ({
  open,
  sourceDate,
  selectedIds,
  onClose,
  onCopySuccess,
}) => {
  const [destinationDate, setDestinationDate] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleCopy = async () => {
    // バリデーション: 日付が入力されているか
    if (!destinationDate) {
      setError('コピー先の日付を入力してください');
      return;
    }

    // バリデーション: 同じ日付ではないか
    if (sourceDate === destinationDate) {
      setError('コピー先の日付は異なる日付を選択してください');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await copyMultipleContents({
        source_date: sourceDate,
        destination_date: destinationDate,
        content_ids: selectedIds,
      });

      if (response.success) {
        // コピー成功
        alert(`${response.copied_count}件の支出をコピーしました`);
        setDestinationDate('');
        onClose();
        onCopySuccess(); // 親コンポーネントでカレンダーを再読み込み
      } else {
        // エラーレスポンス
        setError(response.message || 'コピー処理に失敗しました');
      }
    } catch (err: any) {
      console.error('Copy error:', err);
      setError('コピー処理中にエラーが発生しました');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setDestinationDate('');
    setError(null);
    onClose();
  };

  return (
    <Dialog open={open} onClose={handleClose}>
      <DialogTitle>コピー先の日付を選択</DialogTitle>
      <DialogContent sx={{ minWidth: 400 }}>
        <TextField
          fullWidth
          type="date"
          label="コピー先の日付"
          value={destinationDate}
          onChange={(e) => {
            setDestinationDate(e.target.value);
            setError(null); // エラーをクリア
          }}
          margin="normal"
          InputLabelProps={{
            shrink: true,
          }}
          disabled={loading}
        />

        {error && (
          <Alert severity="error" sx={{ marginTop: 2 }}>
            {error}
          </Alert>
        )}

        {selectedIds.length > 0 && (
          <Alert severity="info" sx={{ marginTop: 2 }}>
            {selectedIds.length}件の支出をコピーします
          </Alert>
        )}
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose} disabled={loading}>
          キャンセル
        </Button>
        <Button
          onClick={handleCopy}
          disabled={loading || selectedIds.length === 0}
          variant="contained"
          startIcon={loading && <CircularProgress size={20} />}
        >
          {loading ? '処理中...' : 'コピー'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default CopyModal;
