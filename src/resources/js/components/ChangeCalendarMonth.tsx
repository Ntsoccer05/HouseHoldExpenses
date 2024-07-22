import { Box } from "@mui/material";
import { useState } from "react";
import { LocalizationProvider } from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFnsV3";
import { MobileDatePicker } from "@mui/x-date-pickers";
import { ja } from "date-fns/locale";
import { startOfMonth } from "date-fns";
import { useAppContext } from "../context/AppContext";
import FullCalendar from "@fullcalendar/react";
import { TextField } from "@mui/material";
import { styled } from "@mui/system";

interface ChangeCalendarMonthProps {
    calendarRef: FullCalendar;
}

const ChangeCalendarMonth = ({ calendarRef }: ChangeCalendarMonthProps) => {
    const { currentMonth, setCurrentMonth } = useAppContext();
    const [open, setOpen] = useState<boolean>(false);

    const closeModal = (newMonth: Date) => {
        if (newMonth) {
            // setCurrentMonth(newMonth);
            setCurrentMonth((prevDate) => {
                if (prevDate !== newMonth) {
                    if (calendarRef) {
                        let calendarApi = calendarRef.getApi();
                        // 指定した月に移動
                        calendarApi.gotoDate(startOfMonth(newMonth));
                    }
                }
                // currentMonthにnewMonthをセット
                return newMonth;
            });
        }
        setOpen(false); // 選択時にモーダルを閉じる
    };

    const myStyle = {
        "& .MuiInputBase-root": {
            border: "none",
        },
        "& .MuiOutlinedInput-root": {
            "& fieldset": {
                border: "none",
            },
        },
        "& .MuiInput-underline:before": {
            borderBottom: "none",
        },
        "& .MuiInput-underline:hover:before": {
            borderBottom: "none",
        },
        "& .MuiInput-underline:after": {
            borderBottom: "none",
        },
    };

    return (
        <LocalizationProvider
            dateAdapter={AdapterDateFns}
            adapterLocale={ja}
            dateFormats={{ monthAndYear: "yyyy年 MM月" }}
        >
            <Box
                sx={{
                    display: "flex",
                    justifyContent: "left",
                }}
            >
                <MobileDatePicker
                    value={currentMonth}
                    open={open}
                    onOpen={() => setOpen(true)}
                    onClose={() => setOpen(false)}
                    label="年月を選択"
                    onMonthChange={closeModal}
                    sx={{
                        mx: 2,
                        // background: "transparent",
                        position: "absolute",
                        top: { xs: "183px", sm: "202px", md: "205px" },
                        left: { xs: "0%", md: "235px" },
                        "& .MuiInputBase-root": {
                            color: "transparent",
                            border: "none",
                        },
                        "& .MuiOutlinedInput-root": {
                            "& fieldset": {
                                border: "none",
                            },
                        },
                    }}
                    views={["year", "month"]}
                    format="yyyy年MM月"
                    slotProps={{
                        toolbar: {
                            toolbarFormat: "yyyy年 M月",
                        },
                        calendarHeader: {
                            format: "yyyy年 M月",
                        },
                    }}
                />
            </Box>
        </LocalizationProvider>
    );
};

export default ChangeCalendarMonth;
