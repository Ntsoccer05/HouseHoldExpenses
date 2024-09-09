import React, {
    ReactNode,
    createContext,
    useContext,
    useEffect,
    useState,
} from "react";
import {
    BaseUserCategory,
    CategoryItem,
    LoginUser,
    Transaction,
} from "../types/index";
import { Schema } from "../validations/schema";
// import {
//   addDoc,
//   collection,
//   deleteDoc,
//   doc,
//   updateDoc,
// } from "firebase/firestore";
// import { db } from "../firebase";
// import { isFirestoreError } from "../utils/errorHandling";
import { useMediaQuery, useTheme } from "@mui/material";
import axios from "axios";

//コンテキスト
interface AppContextType {
    transactions: Transaction[];
    setTransactions: React.Dispatch<React.SetStateAction<Transaction[]>>;
    currentMonth: Date;
    setCurrentMonth: React.Dispatch<React.SetStateAction<Date>>;
    isLoading: boolean;
    setIsLoading: React.Dispatch<React.SetStateAction<boolean>>;
    isMobile: boolean;
    setIsLogigned: React.Dispatch<React.SetStateAction<boolean>>;
    //以下から関数（追加、削除、更新）
    onSaveTransaction: (transaction: Schema) => Promise<void>;
    onDeleteTransaction: (
        transactionIds: string | readonly string[]
    ) => Promise<void>;
    onUpdateTransaction: (
        transaction: Schema,
        transactionId: string
    ) => Promise<void>;
    LoginUser: LoginUser | undefined;
    setLoginUser: React.Dispatch<React.SetStateAction<LoginUser | undefined>>;
    getLoginUser: () => Promise<void>;
    getIncomeCategory: () => Promise<void>;
    getExpenseCategory: () => Promise<void>;
    IncomeCategories: CategoryItem[] | undefined;
    ExpenseCategories: CategoryItem[] | undefined;
}

// createContextでグローバルにする値を設定 AppContext.Providerのvalueの設定値の型を指定する必要がある
const AppContext = createContext<AppContextType | undefined>(undefined);

interface AppProviderProps {
    children: ReactNode;
}

// プロバイダーコンポーネント
export const AppProvider = ({ children }: AppProviderProps) => {
    const theme = useTheme();
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [isLogigned, setIsLogigned] = useState<boolean>(false);
    const [LoginUser, setLoginUser] = useState<LoginUser | undefined>();
    const [IncomeCategories, setIncomeCategories] = useState<CategoryItem[]>(
        []
    );
    const [ExpenseCategories, setExpenseCategories] = useState<CategoryItem[]>(
        []
    );
    const isMobile = useMediaQuery(theme.breakpoints.down("lg"));

    //ログインユーザー取得処理
    const getLoginUser = async () => {
        try {
            await axios.get("/api/user").then((res) => {
                setLoginUser(res.data);
            });
        } catch (err) {
            console.log(err);
        }
    };

    //収入カテゴリー取得処理
    const getIncomeCategory = async () => {
        try {
            LoginUser &&
                (await axios
                    .get("/api/IncomeCategory", {
                        params: {
                            user_id: LoginUser.id,
                        },
                    })
                    .then((res) => {
                        if (res.data.incomeUserCategory) {
                            const responseIncomeCategory =
                                res.data.incomeUserCategory.map(
                                    (incomeCategory: BaseUserCategory) => {
                                        return {
                                            id: incomeCategory.id,
                                            filtered_id:
                                                incomeCategory.filtered_id,
                                            fixed_category_id:
                                                incomeCategory.fixed_category_id,
                                            label: incomeCategory.content,
                                            icon: incomeCategory.icon,
                                            deleted: incomeCategory.deleted,
                                        };
                                    }
                                );
                            setIncomeCategories(responseIncomeCategory);
                        }
                    })
                    .catch((err) => {}));
        } catch (err) {
            console.log(err);
        }
    };

    //収入カテゴリー取得処理
    const getExpenseCategory = async () => {
        try {
            LoginUser &&
                (await axios
                    .get("/api/ExpenseCategory", {
                        params: {
                            user_id: LoginUser.id,
                        },
                    })
                    .then((res) => {
                        if (res.data.expenseUserCategory) {
                            const responseExpenseCategory =
                                res.data.expenseUserCategory.map(
                                    (expenseCategory: BaseUserCategory) => {
                                        return {
                                            id: expenseCategory.id,
                                            filtered_id:
                                                expenseCategory.filtered_id,
                                            fixed_category_id:
                                                expenseCategory.fixed_category_id,
                                            label: expenseCategory.content,
                                            icon: expenseCategory.icon,
                                            deleted: expenseCategory.deleted,
                                        };
                                    }
                                );
                            setExpenseCategories(responseExpenseCategory);
                        }
                    })
                    .catch((err) => {}));
        } catch (err) {
            console.log(err);
        }
    };

    useEffect(() => {
        isLogigned && getLoginUser();
    }, [isLogigned]);

    useEffect(() => {
        getIncomeCategory();
        getExpenseCategory();
    }, [LoginUser]);

    //取引を保存する処理
    const onSaveTransaction = async (transaction: Schema) => {
        // try {
        //     //firestoreにデータを保存
        //     const docRef = await addDoc(
        //         collection(db, "Transactions"),
        //         transaction
        //     );
        //     const newTransaction = {
        //         id: docRef.id,
        //         ...transaction,
        //     } as Transaction;
        //     setTransactions((prevTransaction) => [
        //         ...prevTransaction,
        //         newTransaction,
        //     ]);
        // } catch (err) {
        //     if (isFirestoreError(err)) {
        //         console.error("firestoreのエラーは：", err);
        //     } else {
        //         console.error("一般的なエラーは:", err);
        //     }
        // }
    };

    //削除処理
    const onDeleteTransaction = async (
        transactionIds: string | readonly string[]
    ) => {
        // try {
        //     const idsToDelete = Array.isArray(transactionIds)
        //         ? transactionIds
        //         : [transactionIds];
        //     for (const id of idsToDelete) {
        //         //firestoreのデータ削除
        //         await deleteDoc(doc(db, "Transactions", id));
        //     }
        //     //複数の取引を削除可能
        //     const filterdTransactions = transactions.filter(
        //         (transaction) => !idsToDelete.includes(transaction.id)
        //     );
        //     setTransactions(filterdTransactions);
        // } catch (err) {
        //     if (isFirestoreError(err)) {
        //         console.error("firestoreのエラーは：", err);
        //     } else {
        //         console.error("一般的なエラーは:", err);
        //     }
        // }
    };

    //更新処理
    const onUpdateTransaction = async (
        transaction: Schema,
        transactionId: string
    ) => {
        // try {
        //     //firestore更新処理
        //     const docRef = doc(db, "Transactions", transactionId);
        //     await updateDoc(docRef, transaction);
        //     //フロント更新
        //     const updatedTransactions = transactions.map((t) =>
        //         t.id === transactionId ? { ...t, ...transaction } : t
        //     ) as Transaction[];
        //     setTransactions(updatedTransactions);
        // } catch (err) {
        //     if (isFirestoreError(err)) {
        //         console.error("firestoreのエラーは：", err);
        //     } else {
        //         console.error("一般的なエラーは:", err);
        //     }
        // }
    };

    return (
        // valueで設定した値をchildrenで受け取ることができる
        <AppContext.Provider
            value={{
                transactions,
                setTransactions,
                currentMonth,
                setCurrentMonth,
                isLoading,
                setIsLoading,
                isMobile,
                setIsLogigned,
                onSaveTransaction,
                onDeleteTransaction,
                onUpdateTransaction,
                LoginUser,
                setLoginUser,
                getLoginUser,
                IncomeCategories,
                ExpenseCategories,
                getIncomeCategory,
                getExpenseCategory,
            }}
        >
            {children}
        </AppContext.Provider>
    );
};

// コンテキストを使用するためのカスタムフック
export const useAppContext = () => {
    // useContextでvalueに設定した値を取得できる
    const context = useContext(AppContext);
    if (!context) {
        throw new Error(
            "useAppContextは、AppProvider内で使用する必要があります。"
        );
    }
    return context;
};
