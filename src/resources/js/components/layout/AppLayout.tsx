import * as React from "react";
import AppBar from "@mui/material/AppBar";
import Box from "@mui/material/Box";
import CssBaseline from "@mui/material/CssBaseline";
import IconButton from "@mui/material/IconButton";
import MenuIcon from "@mui/icons-material/Menu";
import Toolbar from "@mui/material/Toolbar";
import Typography from "@mui/material/Typography";
import { Outlet } from "react-router-dom";
import SideBar from "../common/SideBar";
import axios from "axios";
import { Transaction } from "../../types";
import { useAppContext } from "../../context/AppContext";
// import { collection, getDocs } from "firebase/firestore";
// import { db } from "../../firebase";
const drawerWidth = 240;

export default function AppLayout() {
    const [mobileOpen, setMobileOpen] = React.useState(false);
    const { LoginUser, setTransactions, setIsLoading } = useAppContext();

    const handleDrawerToggle = () => {
        setMobileOpen(!mobileOpen);
    };

    //家計簿データを全て取得
    React.useEffect(() => {
        if (LoginUser) {
            const fecheTransactions = async () => {
                try {
                    const querySnapshot = await axios.get(
                        "/api/getTransactions",
                        {
                            params: { user_id: LoginUser.id },
                        }
                    );
                    if (querySnapshot.data.transactions) {
                        const transactionsData =
                            querySnapshot.data.transactions.map(
                                (doc: Transaction) => {
                                    return {
                                        ...doc,
                                    } as Transaction;
                                }
                            );
                        setTransactions(transactionsData);
                    }
                } catch (err) {
                    console.error("一般的なエラーは:", err);
                } finally {
                    setIsLoading(false);
                }
            };
            fecheTransactions();
        }
    }, [LoginUser]);

    const topImgLogoStyle: React.CSSProperties = {
        width: "260px",
        height: "50px",
        objectFit: "cover",
        marginRight: "10px",
    };

    return (
        <Box
            sx={{
                display: "flex",
                bgcolor: (theme) => theme.palette.grey[100],
                minHeight: "100vh",
            }}
        >
            <CssBaseline />

            {/* ヘッダー */}
            <AppBar
                position="fixed"
                sx={{
                    width: { md: `calc(100% - ${drawerWidth}px)` },
                    ml: { md: `${drawerWidth}px` },
                    bgcolor: "#fff",
                }}
            >
                <Toolbar>
                    <IconButton
                        color="inherit"
                        aria-label="open drawer"
                        edge="start"
                        onClick={handleDrawerToggle}
                        sx={{ mr: 2, display: { md: "none" }, color: "black" }}
                    >
                        <MenuIcon />
                    </IconButton>
                    <Typography
                        variant="h6"
                        noWrap
                        component="div"
                        sx={{
                            display: "flex",
                            alignItems: "center",
                            margin: "0 auto",
                        }}
                    >
                        {/* <span className="topTitle">スマート家計簿</span> */}
                        <img
                            style={topImgLogoStyle}
                            // publicフォルダ内のロゴを参照
                            src="/logo/スマカケ.webp"
                            alt="toplogo"
                        />
                    </Typography>
                </Toolbar>
            </AppBar>

            {/* サイドバー */}
            <SideBar
                drawerWidth={drawerWidth}
                mobileOpen={mobileOpen}
                handleDrawerToggle={handleDrawerToggle}
            />

            {/* メインコンテンツ */}
            <Box
                component="main"
                sx={{
                    flexGrow: 1,
                    p: 3,
                    width: { md: `calc(100% - ${drawerWidth}px)` },
                }}
            >
                <Toolbar />
                {/* Outletで子コンポーネントにレイアウトを継承する */}
                <Outlet />
            </Box>
        </Box>
    );
}
