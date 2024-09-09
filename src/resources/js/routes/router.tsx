import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import Home from "../pages/Home";
import Report from "../pages/Report";
import NoMatch from "../pages/NoMatch";
import AppLayout from "../components/layout/AppLayout";
import { theme } from "../theme/theme";
import { ThemeProvider } from "@emotion/react";
import { CssBaseline } from "@mui/material";

import { AppProvider } from "../context/AppContext";
import { CategoryProvider } from "../context/CategoryContext";
import Login from "../pages/Login";
import Register from "../pages/Register";
import VerifyEmail from "../components/Auth/VerifyEmail";
import PasswordForget from "../pages/PasswordForget";
import ResetPassword from "../pages/ResetPassword";
import Category from "../pages/Category";
import PrivateRoute from "./PrivateRoute";

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
                            <Route
                                index
                                // PrivateRoute：ログインしていなかったらログイン画面へリダイレクト
                                element={
                                    <PrivateRoute>
                                        <Home />
                                    </PrivateRoute>
                                }
                            />
                            <Route path="/login" element={<Login />} />
                            <Route path="/register" element={<Register />} />
                            <Route
                                path="/report"
                                element={
                                    <PrivateRoute>
                                        <Report />
                                    </PrivateRoute>
                                }
                            />
                            {/* routerの中でcontextを一部に使いたいときはelementの中で指定する */}
                            <Route
                                path="/category"
                                element={
                                    <PrivateRoute>
                                        <CategoryProvider>
                                            <Category />
                                        </CategoryProvider>
                                    </PrivateRoute>
                                }
                            />
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
