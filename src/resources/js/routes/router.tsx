// import { Home } from "../pages/Home";
// import { Nomatch } from "../pages/Nomatch";
// import { Report } from "../pages/Report";
// import AppLayout from "../context/AppContext";
// import React from "react";
// import { Route, Routes } from "react-router-dom";

// function Router() {
//     return (
//         <Router>
//             <Routes>
//                 <Route path="/" element={<AppLayout />}>
//                     {/* 親と同じパスはindexと記述できる */}
//                     <Route index element={<Home />} />
//                     <Route path="/report" element={<Report />} />
//                     {/* 最後にpath='*'で上記に当てはまらない全てのページを指す */}
//                     <Route path="*" element={<Nomatch />} />
//                 </Route>
//             </Routes>
//         </Router>
//     );
// }

// export default Router;

import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import Home from "../pages/Home";
import Report from "../pages/Report";
import NoMatch from "../pages/NoMatch";
import AppLayout from "../components/layout/AppLayout";
import { theme } from "../theme/theme";
import { ThemeProvider } from "@emotion/react";
import { CssBaseline } from "@mui/material";

import { AppProvider } from "../context/AppContext";
import Login from "../pages/Login";
import Register from "../pages/Register";
import VerifyEmail from "../components/Auth/VerifyEmail";

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
