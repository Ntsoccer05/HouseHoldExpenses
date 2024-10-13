import { Box, Button, TextField, InputAdornment } from "@mui/material";
import { useState } from "react";
import { LocalizationProvider } from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFnsV3";
import { DatePicker } from "@mui/x-date-pickers/DatePicker";
import { ja } from "date-fns/locale";
import { addMonths } from "date-fns";
import { useAppContext } from "../context/AppContext";
import TodayIcon from "@mui/icons-material/Today";

const MonthSelector = () => {
    const { currentMonth, setCurrentMonth } = useAppContext();
    const [isOpen, setIsOpen] = useState(false);

    const handleDateChange = (newDate: Date | null) => {
        if (newDate) {
            setCurrentMonth(newDate);
        } else {
            // Handle the case when the input is cleared
            setCurrentMonth(new Date());
        }
    };

    //先月ボタンを押したときの処理
    const handlePreviousMonth = () => {
        const previousMonth = addMonths(currentMonth, -1);
        setCurrentMonth(previousMonth);
    };

    //次月ボタンを押したときの処理
    const handleNextMonth = () => {
        const nextMonth = addMonths(currentMonth, 1);
        setCurrentMonth(nextMonth);
    };

    const handleTextFieldClick = () => {
        setIsOpen(true);
    };

    const handleIconClick = () => {
        setIsOpen(true); // Open the DatePicker when CalendarIcon is clicked
    };

    return (
        <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={ja}>
            <Box
                sx={{
                    display: "flex",
                    justifyContent: "center",
                    alignItems: "center",
                }}
            >
                <Button
                    onClick={handlePreviousMonth}
                    color={"error"}
                    variant="contained"
                >
                    先月
                </Button>
                <DatePicker
                    onChange={handleDateChange}
                    value={currentMonth}
                    label="年月を選択"
                    sx={{ mx: 2, background: "white" }}
                    views={["year", "month"]}
                    format="yyyy/MM"
                    open={isOpen} // Control the open state
                    onClose={() => setIsOpen(false)} // Handle closing
                    slots={{ textField: TextField }} // Use 'slots' to render TextField
                    slotProps={{
                        textField: {
                            onClick: handleTextFieldClick, // Open DatePicker on TextField click
                            sx: { mx: 2, background: "white" },
                            onKeyDown: (event) => {
                                // Handle key down events
                                if (
                                    event.key === "Delete" ||
                                    event.key === "Backspace"
                                ) {
                                    handleDateChange(null); // Reset on delete
                                }
                            },
                            InputProps: {
                                endAdornment: (
                                    <InputAdornment position="end">
                                        <TodayIcon
                                            onClick={handleIconClick} // Open on icon click
                                            sx={{ cursor: "pointer" }}
                                        />
                                    </InputAdornment>
                                ),
                            },
                        },
                        calendarHeader: {
                            format: "yyyy年MM月",
                        },
                    }}
                />
                <Button
                    onClick={handleNextMonth}
                    color={"primary"}
                    variant="contained"
                >
                    次月
                </Button>
            </Box>
        </LocalizationProvider>
    );
};

export default MonthSelector;
