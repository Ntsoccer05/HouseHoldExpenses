import React, { ReactNode, createContext, useContext, useState } from "react";
import { Transaction, TransactionData } from "../types/index";
import { Schema } from "../validations/schema";
import { useTheme } from "@mui/material";
import axios from "axios";
import { useAppContext } from "../context/AppContext";

//コンテキスト
interface TransactionContext {
    //以下から関数（追加、削除、更新）
    onSaveTransaction: (transaction: Schema) => Promise<void>;
    onDeleteTransaction: (
        transactionIds: string | readonly string[]
    ) => Promise<void>;
    onUpdateTransaction: (
        transaction: Schema,
        transactionId: string
    ) => Promise<void>;
}

// createContextでグローバルにする値を設定 AppContext.Providerのvalueの設定値の型を指定する必要がある
const TransactionContext = createContext<TransactionContext | undefined>(
    undefined
);

interface TransactionProviderProps {
    children: ReactNode;
}

// プロバイダーコンポーネント
export const TransactionProvider = ({ children }: TransactionProviderProps) => {
    const theme = useTheme();

    const {
        LoginUser,
        transactions,
        setTransactions,
        ExpenseCategories,
        IncomeCategories,
    } = useAppContext();

    const addCategoryIcon = (transaction: TransactionData) => {
        if (transaction.type === "expense") {
            const ExpenseCategory = ExpenseCategories?.filter((category) => {
                return category.label === transaction.category;
            });
            if (ExpenseCategory) {
                return ExpenseCategory[0].icon;
            }
        } else if (transaction.type === "income") {
            const IncomeCategory = IncomeCategories?.filter((category) => {
                return category.label === transaction.category;
            });
            if (IncomeCategory) {
                return IncomeCategory[0].icon;
            }
        }
    };

    //取引を保存する処理
    const onSaveTransaction = async (transaction: TransactionData) => {
        try {
            transaction.icon = addCategoryIcon(transaction);
            //データを保存
            const docRef = await axios.post("/api/addTransaction", {
                transaction: transaction,
                user_id: LoginUser?.id,
            });
            const newTransaction = {
                id: docRef.data.id,
                ...transaction,
            } as Transaction;
            setTransactions((prevTransaction) => [
                ...prevTransaction,
                newTransaction,
            ]);
        } catch (err) {
            console.error("一般的なエラーは:", err);
        }
    };

    //削除処理
    const onDeleteTransaction = async (
        transactionIds: string | readonly string[]
    ) => {
        try {
            const idsToDelete = Array.isArray(transactionIds)
                ? transactionIds
                : [transactionIds];
            for (const id of idsToDelete) {
                //firestoreのデータ削除
                await axios.post("/api/deleteTransaction", {
                    transactionId: id,
                    user_id: LoginUser?.id,
                });
            }
            //複数の取引を削除可能
            const filterdTransactions = transactions.filter(
                (transaction) => !idsToDelete.includes(transaction.id)
            );
            setTransactions(filterdTransactions);
        } catch (err) {
            console.error("一般的なエラーは:", err);
        }
    };

    //更新処理
    const onUpdateTransaction = async (
        transaction: TransactionData,
        transactionId: string
    ) => {
        try {
            transaction.icon = addCategoryIcon(transaction);
            //データを保存
            const docRef = await axios.post("/api/updateTransaction", {
                transaction: transaction,
                transactionId: transactionId,
                user_id: LoginUser?.id,
            });
            const newTransaction = {
                id: docRef.data.id,
                ...transaction,
            } as Transaction;
            //フロント更新
            const updatedTransactions = transactions.map((t) =>
                t.id === transactionId ? { ...t, ...transaction } : t
            ) as Transaction[];
            setTransactions(updatedTransactions);
        } catch (err) {
            console.error("一般的なエラーは:", err);
        }
    };

    return (
        // valueで設定した値をchildrenで受け取ることができる
        <TransactionContext.Provider
            value={{
                onSaveTransaction,
                onDeleteTransaction,
                onUpdateTransaction,
            }}
        >
            {children}
        </TransactionContext.Provider>
    );
};

// コンテキストを使用するためのカスタムフック
export const useTransactionContext = () => {
    // useContextでvalueに設定した値を取得できる
    const context = useContext(TransactionContext);
    if (!context) {
        throw new Error(
            "useTransactionContextは、TransactionProvider内で使用する必要があります。"
        );
    }
    return context;
};
