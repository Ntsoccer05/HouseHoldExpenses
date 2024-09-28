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
import OnlyPublicRoute from "./OnlyPublicRoute";

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
                                element={<Home />}
                            />
                            <Route
                                path="/login"
                                element={
                                    <OnlyPublicRoute>
                                        <Login />
                                    </OnlyPublicRoute>
                                }
                            />
                            <Route
                                path="/register"
                                element={
                                    <OnlyPublicRoute>
                                        <Register />
                                    </OnlyPublicRoute>
                                }
                            />
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
                                    <CategoryProvider>
                                        <Category />
                                    </CategoryProvider>
                                }
                            />
                            <Route
                                path="/api/email/verify/:id/:hash"
                                element={
                                    <OnlyPublicRoute>
                                        <VerifyEmail />
                                    </OnlyPublicRoute>
                                }
                            />
                            <Route
                                path="/api/password/reset"
                                element={
                                    <OnlyPublicRoute>
                                        <ResetPassword />
                                    </OnlyPublicRoute>
                                }
                            />
                            <Route
                                path="/password/forget"
                                element={
                                    <OnlyPublicRoute>
                                        <PasswordForget />
                                    </OnlyPublicRoute>
                                }
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
