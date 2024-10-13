import FullCalendar from "@fullcalendar/react";
import React, { memo, useEffect, useState } from "react";
import dayGridPlugin from "@fullcalendar/daygrid";
import jaLocale from "@fullcalendar/core/locales/ja";
import {
    DatesSetArg,
    DayCellContentArg,
    EventContentArg,
} from "@fullcalendar/core";
import { Balance, CalendarContent } from "../types";
import { calculateDailyBalances } from "../utils/financeCalculations";
import { formatCurrency } from "../utils/formatting";
import interactionPlugin, { DateClickArg } from "@fullcalendar/interaction";
import { useTheme } from "@mui/material";
import {
    isSameMonth,
    startOfMonth,
    endOfMonth,
    format,
    subMonths,
    addMonths,
} from "date-fns";
import useMonthlyTransactions from "../hooks/useMonthlyTransactions";
import { useAppContext } from "../context/AppContext";
import * as holiday_jp from "@holiday-jp/holiday_jp";

interface Holiday {
    date: Date;
    name: string;
    name_en: string;
    week: string;
    week_en: string;
}
interface HolidayEvent {
    start: string;
    title: string;
    display: string;
    backgroundColor: string;
}

interface CalendarProps {
    setCurrentDay: React.Dispatch<React.SetStateAction<string>>;
    currentDay: string;
    today: string;
    onDateClick: (dateInfo: DateClickArg) => void;
    calendarRef: React.LegacyRef<FullCalendar>;
}
const Calendar = memo(
    ({
        setCurrentDay,
        currentDay,
        today,
        onDateClick,
        calendarRef,
    }: CalendarProps) => {
        const monthlyTransactions = useMonthlyTransactions();
        const { setCurrentMonth, currentMonth } = useAppContext();
        const theme = useTheme();
        // 祝日をセット
        const [holidays, setHolidays] = useState<Holiday[]>();
        const [holidayEvents, setHolidayEvents] = useState<HolidayEvent[]>([]);

        // 1.各日付の収支を計算する関数（呼び出し）🎃
        const dailyBalances = calculateDailyBalances(monthlyTransactions);

        // ***2.FullCalendar用のイベントを生成する関数📅
        const createCalendarEvents = (
            dailyBalances: Record<string, Balance>
        ): CalendarContent[] => {
            return Object.keys(dailyBalances).map((date) => {
                const { income, expense, balance } = dailyBalances[date];
                return {
                    start: date,
                    income: formatCurrency(income),
                    expense: formatCurrency(expense),
                    balance: formatCurrency(balance),
                };
            });
        };
        // ******FullCalendar用のイベントを生成する関数ここまで**********

        const calendarEvents = createCalendarEvents(dailyBalances);

        const backgroundEvent = {
            start: currentDay,
            display: "background",
            backgroundColor: theme.palette.incomeColor.light,
        };

        //カレンダーイベントの見た目を作る関数
        const renderEventContent = (eventInfo: EventContentArg) => {
            return (
                <div className="custom-event">
                    <div
                        className="money custom-event-content"
                        id="event-income"
                    >
                        {eventInfo.event.extendedProps.income}
                    </div>

                    <div
                        className="money custom-event-content"
                        id="event-expense"
                    >
                        {eventInfo.event.extendedProps.expense}
                    </div>

                    <div
                        className="money custom-event-content"
                        id="event-balance"
                    >
                        {eventInfo.event.extendedProps.balance}
                    </div>
                </div>
            );
        };

        //月の日付取得
        const handleDateSet = (datesetInfo: DatesSetArg) => {
            const currentMonth = datesetInfo.view.currentStart;

            setCurrentMonth(currentMonth);
            // ---祝日をセット
            const thisHolidays = holiday_jp.between(
                startOfMonth(subMonths(currentMonth, 1)),
                endOfMonth(addMonths(currentMonth, 1))
            );
            setHolidays(thisHolidays);
            // ---
            const todayDate = new Date();
            // isSameMonth()は第一引数と第二引数の月が等しいか判別（今日ボタン押下と他ボタン押下を判別するため）
            if (isSameMonth(todayDate, currentMonth)) {
                setCurrentDay(today);
            }
        };
        useEffect(() => {
            setHolidayEvents(backgroundHoliday());
        }, [holidays]);

        const backgroundHoliday = (): HolidayEvent[] => {
            if (holidays) {
                return holidays.map((holiday) => {
                    return {
                        start: format(holiday.date, "yyyy-MM-dd"),
                        title: holiday.name,
                        display: "background",
                        backgroundColor: theme.palette.holidayColor.main,
                    };
                });
            } else {
                return [];
            }
        };

        const handleDayCellClassNames = (arg: DayCellContentArg): string[] => {
            const cellDate = arg.date;

            if (calendarRef.current) {
                const api = calendarRef.current.getApi();
                const viewDate = api.getDate(); // This gives us the first date of the current view

                // Check if the year or month of the cell date does not match the view date
                if (
                    cellDate.getFullYear() !== viewDate.getFullYear() ||
                    cellDate.getMonth() !== viewDate.getMonth()
                ) {
                    return ["non-current-month"];
                }
            }

            return [];
        };

        return (
            <>
                {/* <button onClick={goNext}>next</button> */}
                <FullCalendar
                    ref={calendarRef}
                    locale={jaLocale}
                    plugins={[dayGridPlugin, interactionPlugin]}
                    initialView="dayGridMonth"
                    events={[
                        ...calendarEvents,
                        ...holidayEvents,
                        backgroundEvent,
                    ]}
                    eventContent={renderEventContent}
                    dayCellClassNames={handleDayCellClassNames}
                    datesSet={handleDateSet}
                    dateClick={onDateClick}
                    buttonText={{
                        today: "今月",
                    }}
                    // 翌月、前月を非表示
                    // showNonCurrentDates={false}
                    fixedWeekCount={false}
                />
            </>
        );
    }
);

export default Calendar;
