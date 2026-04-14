import React, { useState } from 'react';
import {
  Box,
  Checkbox,
  Button,
  Typography,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  ListItemIcon,
  Paper,
} from '@mui/material';
import { CopyModal } from './CopyModal';

interface Transaction {
  id: number;
  date: string;
  amount: number;
  content: string;
  type: 'income' | 'expense';
  category: string;
  icon: string;
}

interface TransactionListProps {
  transactions: Transaction[];
  recordedDate: string;
  onCopySuccess?: () => void;
}

/**
 * トランザクション一覧コンポーネント（チェックボックス付き）
 */
export const TransactionList: React.FC<TransactionListProps> = ({
  transactions,
  recordedDate,
  onCopySuccess = () => {},
}) => {
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [copyModalOpen, setCopyModalOpen] = useState(false);

  // チェックボックス変更ハンドラ
  const handleCheckChange = (id: number) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((sid) => sid !== id) : [...prev, id]
    );
  };

  // 全て選択
  const handleSelectAll = () => {
    if (selectedIds.length === transactions.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(transactions.map((t) => t.id));
    }
  };

  // コピーボタンクリック
  const handleCopyClick = () => {
    if (selectedIds.length === 0) {
      alert('コピーする支出を選択してください');
      return;
    }
    setCopyModalOpen(true);
  };

  // コピー成功ハンドラ
  const handleCopySuccess = () => {
    setSelectedIds([]);
    onCopySuccess();
  };

  const expenseTransactions = transactions.filter((t) => t.type === 'expense');
  const incomeTransactions = transactions.filter((t) => t.type === 'income');

  return (
    <Box sx={{ padding: 2 }}>
      {/* 全て選択ボタン */}
      {transactions.length > 0 && (
        <Box sx={{ marginBottom: 2, display: 'flex', alignItems: 'center', gap: 1 }}>
          <Checkbox
            checked={selectedIds.length === transactions.length && transactions.length > 0}
            indeterminate={selectedIds.length > 0 && selectedIds.length < transactions.length}
            onChange={handleSelectAll}
          />
          <Typography variant="body2">
            {selectedIds.length === 0 ? '全て選択' : `${selectedIds.length}件選択`}
          </Typography>
        </Box>
      )}

      {/* 支出 */}
      {expenseTransactions.length > 0 && (
        <Paper sx={{ marginBottom: 2 }}>
          <Typography variant="subtitle1" sx={{ padding: 1, fontWeight: 'bold' }}>
            支出
          </Typography>
          <List>
            {expenseTransactions.map((transaction) => (
              <ListItem
                key={transaction.id}
                secondaryAction={
                  <Typography variant="body2" sx={{ fontWeight: 'bold', color: 'error.main' }}>
                    ¥{transaction.amount.toLocaleString()}
                  </Typography>
                }
                disablePadding
              >
                <ListItemButton
                  role={undefined}
                  onClick={() => handleCheckChange(transaction.id)}
                  dense
                >
                  <ListItemIcon>
                    <Checkbox
                      edge="start"
                      checked={selectedIds.includes(transaction.id)}
                      tabIndex={-1}
                      disableRipple
                    />
                  </ListItemIcon>
                  <ListItemText
                    primary={transaction.category}
                    secondary={transaction.content}
                  />
                </ListItemButton>
              </ListItem>
            ))}
          </List>
        </Paper>
      )}

      {/* 収入 */}
      {incomeTransactions.length > 0 && (
        <Paper sx={{ marginBottom: 2 }}>
          <Typography variant="subtitle1" sx={{ padding: 1, fontWeight: 'bold' }}>
            収入
          </Typography>
          <List>
            {incomeTransactions.map((transaction) => (
              <ListItem
                key={transaction.id}
                secondaryAction={
                  <Typography variant="body2" sx={{ fontWeight: 'bold', color: 'success.main' }}>
                    ¥{transaction.amount.toLocaleString()}
                  </Typography>
                }
                disablePadding
              >
                <ListItemButton
                  role={undefined}
                  onClick={() => handleCheckChange(transaction.id)}
                  dense
                >
                  <ListItemIcon>
                    <Checkbox
                      edge="start"
                      checked={selectedIds.includes(transaction.id)}
                      tabIndex={-1}
                      disableRipple
                    />
                  </ListItemIcon>
                  <ListItemText
                    primary={transaction.category}
                    secondary={transaction.content}
                  />
                </ListItemButton>
              </ListItem>
            ))}
          </List>
        </Paper>
      )}

      {/* コピーボタン */}
      {transactions.length > 0 && (
        <Box sx={{ display: 'flex', gap: 1, marginTop: 2 }}>
          <Button
            variant="contained"
            onClick={handleCopyClick}
            disabled={selectedIds.length === 0}
          >
            {selectedIds.length > 0 ? `${selectedIds.length}件をコピー` : 'コピー'}
          </Button>
        </Box>
      )}

      {/* コピーモーダル */}
      <CopyModal
        open={copyModalOpen}
        sourceDate={recordedDate}
        selectedIds={selectedIds}
        onClose={() => setCopyModalOpen(false)}
        onCopySuccess={handleCopySuccess}
      />

      {/* トランザクションがない場合 */}
      {transactions.length === 0 && (
        <Typography variant="body2" color="textSecondary" sx={{ textAlign: 'center' }}>
          この日付のトランザクションはありません
        </Typography>
      )}
    </Box>
  );
};

export default TransactionList;
