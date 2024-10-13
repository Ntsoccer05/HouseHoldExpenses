import { Box, Button, InputAdornment, TextField } from "@mui/material";
import { useRef, useState } from "react";
import { LocalizationProvider } from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFnsV3";
import { DatePicker } from "@mui/x-date-pickers/DatePicker";
import { ja } from "date-fns/locale";
import { addYears } from "date-fns";
import { useAppContext } from "../context/AppContext";
import TodayIcon from "@mui/icons-material/Today";

const YearSelector = () => {
    const { currentYear, setCurrentYear } = useAppContext();
    const datePickerRef = useRef(null);
    const [isOpen, setIsOpen] = useState(false);

    const handleDateChange = (newDate: Date | null) => {
        if (newDate) {
            setCurrentYear(newDate);
        } else {
            // Handle the case when the input is cleared
            setCurrentYear(new Date());
        }
    };

    //前年ボタンを押したときの処理
    const handlePreviousYear = () => {
        const previousMonth = addYears(currentYear, -1);
        setCurrentYear(previousMonth);
    };

    //来年ボタンを押したときの処理
    const handleNextYear = () => {
        const nextMonth = addYears(currentYear, 1);
        setCurrentYear(nextMonth);
    };

    const handleTextFieldClick = () => {
        setIsOpen(true);
    };

    const handleIconClick = () => {
        setIsOpen(true);
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
                    onClick={handlePreviousYear}
                    color={"error"}
                    variant="contained"
                >
                    前年
                </Button>
                <DatePicker
                    onChange={handleDateChange}
                    value={currentYear}
                    label="年を選択"
                    ref={datePickerRef}
                    sx={{ mx: 2, background: "white" }}
                    views={["year"]}
                    format="yyyy年"
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
                            format: "yyyy年",
                        },
                    }}
                />
                <Button
                    onClick={handleNextYear}
                    color={"primary"}
                    variant="contained"
                >
                    来年
                </Button>
            </Box>
        </LocalizationProvider>
    );
};

export default YearSelector;
