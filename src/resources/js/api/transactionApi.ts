import axios from 'axios';

/**
 * トランザクション API クライアント
 */

// リクエスト・レスポンス型定義
interface CopyMultipleContentsRequest {
  source_date: string;
  destination_date: string;
  content_ids: number[];
}

interface CopyMultipleContentsResponse {
  success: boolean;
  message: string;
  copied_count?: number;
  created_ids?: number[];
  error_code?: string;
  error?: string;
}

/**
 * 複数の支出をコピー
 */
export const copyMultipleContents = async (
  data: CopyMultipleContentsRequest
): Promise<CopyMultipleContentsResponse> => {
  try {
    const response = await axios.post('/api/copyMultipleContents', data);
    return response.data;
  } catch (error: any) {
    if (error.response?.data) {
      return error.response.data;
    }
    throw error;
  }
};

/**
 * トランザクション一覧を取得
 */
export const getTransactions = async (userId: number) => {
  try {
    const response = await axios.post('/api/getTransactions', { user_id: userId });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch transactions:', error);
    throw error;
  }
};

/**
 * トランザクションを作成
 */
export const createTransaction = async (data: any) => {
  try {
    const response = await axios.post('/api/addTransaction', data);
    return response.data;
  } catch (error) {
    console.error('Failed to create transaction:', error);
    throw error;
  }
};

/**
 * トランザクションを更新
 */
export const updateTransaction = async (transactionId: number, data: any) => {
  try {
    const response = await axios.post('/api/updateTransaction', {
      transactionId,
      ...data,
    });
    return response.data;
  } catch (error) {
    console.error('Failed to update transaction:', error);
    throw error;
  }
};

/**
 * トランザクションを削除
 */
export const deleteTransaction = async (transactionId: number) => {
  try {
    const response = await axios.post('/api/deleteTransaction', {
      transactionId,
    });
    return response.data;
  } catch (error) {
    console.error('Failed to delete transaction:', error);
    throw error;
  }
};

/**
 * 月次トランザクションを取得
 */
export const getMonthlyTransaction = async (userId: number, currentMonth: string) => {
  try {
    const response = await axios.get('/api/monthly-transaction', {
      params: {
        user_id: userId,
        currentMonth,
      },
    });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch monthly transactions:', error);
    throw error;
  }
};

/**
 * 年次トランザクションを取得
 */
export const getYearlyTransaction = async (userId: number, currentYear: string) => {
  try {
    const response = await axios.get('/api/yearly-transaction', {
      params: {
        user_id: userId,
        currentYear,
      },
    });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch yearly transactions:', error);
    throw error;
  }
};
