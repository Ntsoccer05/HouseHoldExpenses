import { Box, useMediaQuery, Grid, useTheme } from "@mui/material";
import React, {
    MutableRefObject,
    useCallback,
    useMemo,
    useRef,
    useState,
} from "react";
import MonthlySummary from "../components/MonthlySummary";
import Calendar from "../components/Calendar";
import TransactionMenu from "../components/TransactionMenu";
import TransactionForm from "../components/TransactionForm";
import { Transaction } from "../types";
import { format } from "date-fns";
import { DateClickArg } from "@fullcalendar/interaction";
import useMonthlyTransactions from "../hooks/useMonthlyTransactions";
import { useAppContext } from "../context/AppContext";
import ChangeCalendarMonth from "../components/ChangeCalendarMonth";
import FullCalendar from "@fullcalendar/react";
import "../assets/css/calendar.css";
import { useNavigate } from "react-router-dom";
import { CalendarApi } from "fullcalendar";
import { useTransactionContext } from "../context/TransactionContext";

const Home = () => {
    const today = format(new Date(), "yyyy-MM-dd");
    const [currentDay, setCurrentDay] = useState(today);
    // PCの入力フォーム開閉
    const [isEntryDrawerOpen, setIsEntryDrawerOpen] = useState(false);
    const [selectedTransaction, setSelectedTransaction] =
        useState<Transaction | null>(null);
    const [isMobileDrawerOpen, setIsMobileDrawerOpen] = useState(false);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    // const theme = useTheme();
    // const isMobile = useMediaQuery(theme.breakpoints.down("lg"));

    const { isMobile, LoginUser } = useAppContext();

    //ページ遷移に使用する
    const navigate = useNavigate();

    const calendarRef = useRef<React.LegacyRef<FullCalendar> | FullCalendar>(
        null
    );

    // const monthlyTransactions = useMonthlyTransactions();
    const { monthlyTransactions } = useTransactionContext();

    // 一日分のデータを取得
    const dailyTransactions = useMemo(() => {
        return monthlyTransactions.filter(
            (transaction) => transaction.date === currentDay
        );
    }, [monthlyTransactions, currentDay]);

    const closeForm = () => {
        setSelectedTransaction(null);
        if (isMobile) {
            setIsDialogOpen(!isDialogOpen);
        } else {
            setIsEntryDrawerOpen(!isEntryDrawerOpen);
        }
    };

    // フォームの開閉処理(内訳追加ボタンを押したとき)
    const handleAddTransactionForm = () => {
        if (LoginUser) {
            if (isMobile) {
                setIsDialogOpen(true);
            } else {
                if (selectedTransaction && isEntryDrawerOpen) {
                    setSelectedTransaction(null);
                    return;
                }
                setIsEntryDrawerOpen(!isEntryDrawerOpen);
            }
        } else {
            navigate("/login");
        }
    };
    //取り引きが選択された時の処理
    const handleSelectTransaction = (trnsaction: Transaction) => {
        setSelectedTransaction(trnsaction);
        if (isMobile) {
            setIsDialogOpen(true);
        } else {
            setIsEntryDrawerOpen(true);
        }
    };

    // モバイル用Drawerを閉じる処理
    const handleCloseMobileDrawer = () => {
        setIsMobileDrawerOpen(false);
    };

    // 日付を選択したときの処理
    const handleDateClick = (dateInfo: DateClickArg) => {
        if (isEntryDrawerOpen) {
            setIsEntryDrawerOpen(false);
        }
        const clickedDate = new Date(dateInfo.dateStr);
        const calendarApi: CalendarApi | null = calendarRef.current?.getApi();
        const startDate = calendarApi?.view?.currentStart;
        const endDate = calendarApi?.view?.currentEnd;

        if (
            !startDate ||
            !endDate ||
            clickedDate < startDate ||
            clickedDate >= endDate
        ) {
            return;
        }
        setCurrentDay(dateInfo.dateStr);
        if (isMobile) {
            setIsMobileDrawerOpen(true);
        }
    };

    return (
        <Box sx={{ display: "flex" }}>
            {/* 左側コンテンツ */}
            <Box sx={{ flexGrow: 1, fontSize: { xs: "12px", sm: "1em" } }}>
                <MonthlySummary monthlyTransactions={monthlyTransactions} />
                <Grid
                    item
                    xs={12}
                    sx={{
                        marginBottom: { xs: "13px", sm: 0 },
                    }}
                >
                    {/* 日付選択エリア */}
                    <ChangeCalendarMonth
                        calendarRef={calendarRef.current as FullCalendar}
                    />
                </Grid>
                <Calendar
                    setCurrentDay={setCurrentDay}
                    currentDay={currentDay}
                    today={today}
                    onDateClick={handleDateClick}
                    calendarRef={calendarRef as React.LegacyRef<FullCalendar>}
                />
            </Box>
            {/* 右側コンテンツ */}
            <Box>
                <TransactionMenu
                    dailyTransactions={dailyTransactions}
                    currentDay={currentDay}
                    onAddTransactionForm={handleAddTransactionForm}
                    onSelectTransaction={handleSelectTransaction}
                    // isMobile={isMobile}
                    open={isMobileDrawerOpen}
                    onClose={handleCloseMobileDrawer}
                />
                <TransactionForm
                    onCloseForm={closeForm}
                    isEntryDrawerOpen={isEntryDrawerOpen}
                    currentDay={currentDay}
                    selectedTransaction={selectedTransaction}
                    setSelectedTransaction={setSelectedTransaction}
                    // isMobile={isMobile}
                    isDialogOpen={isDialogOpen}
                    setIsDialogOpen={setIsDialogOpen}
                />
            </Box>
        </Box>
    );
};
export default Home;
