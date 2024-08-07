import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import Home from "../pages/Home";
import Report from "../pages/Report";
import NoMatch from "../pages/NoMatch";
import AppLayout from "../components/layout/AppLayout";
import { theme } from "../theme/theme";
import { ThemeProvider } from "@emotion/react";
import { CssBaseline } from "@mui/material";

import { AppProvider, useAppContext } from "../context/AppContext";
import Login from "../pages/Login";
import Register from "../pages/Register";
import VerifyEmail from "../components/Auth/VerifyEmail";
import PasswordForget from "../pages/PasswordForget";
import ResetPassword from "../pages/ResetPassword";

function DefineRouter() {
    return (
        <ThemeProvider theme={theme}>
            <CssBaseline />
            {/* AppProviderで囲まれている中でvalueで設定した値をグローバルに参照できる */}
            <AppProvider>
                <Router>
                    <Routes>
                        <Route path="/" element={<AppLayout />}>
                            {/* 親と同じパスはindexと記述できる */}
                            <Route index element={<Home />} />
                            <Route path="/login" element={<Login />} />
                            <Route path="/register" element={<Register />} />
                            <Route path="/report" element={<Report />} />
                            <Route
                                path="/api/email/verify/:id/:hash"
                                element={<VerifyEmail />}
                            />
                            <Route
                                path="/api/password/reset"
                                element={<ResetPassword />}
                            />
                            <Route
                                path="/password/forget"
                                element={<PasswordForget />}
                            />
                            {/* 最後にpath='*'で上記に当てはまらない全てのページを指す */}
                            <Route path="*" element={<NoMatch />} />
                        </Route>
                    </Routes>
                </Router>
            </AppProvider>
        </ThemeProvider>
    );
}

export default DefineRouter;
