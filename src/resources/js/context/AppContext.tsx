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
    setLoginFlg: React.Dispatch<React.SetStateAction<number>>;
    loginFlg: number;
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
    const [loginFlg, setLoginFlg] = useState<number>(0);
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
                setLoginFlg(() => 1);
            });
        } catch (err) {
            console.log(err);
            setLoginFlg(() => 2);
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
                                            // fixed_category_id:
                                            //     incomeCategory.fixed_category_id,
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

    React.useEffect(() => {
        getLoginUser();
    }, []);

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
                                            // fixed_category_id:
                                            //     expenseCategory.fixed_category_id,
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
        getIncomeCategory();
        getExpenseCategory();
    }, [LoginUser]);

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
                setLoginFlg,
                loginFlg,
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
