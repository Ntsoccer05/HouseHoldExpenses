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
// import { collection, getDocs } from "firebase/firestore";
// import { db } from "../../firebase";
import { Transaction } from "../../types";
import { useAppContext } from "../../context/AppContext";
import topLogo from "../../logo/スマカケ.webp";
const drawerWidth = 240;

export default function AppLayout() {
    const { setTransactions, setIsLoading } = useAppContext();
    const [mobileOpen, setMobileOpen] = React.useState(false);

    const handleDrawerToggle = () => {
        setMobileOpen(!mobileOpen);
    };

    //firestoreのデータを全て取得
    React.useEffect(() => {
        // const fecheTransactions = async () => {
        //   try {
        //     const querySnapshot = await getDocs(collection(db, "Transactions"));
        //     const transactionsData = querySnapshot.docs.map((doc) => {
        //       return {
        //         ...doc.data(),
        //         id: doc.id,
        //       } as Transaction;
        //     });
        //     setTransactions(transactionsData);
        //   } catch (err) {
        //     if (isFirestoreError(err)) {
        //       console.error("firestoreのエラーは：", err);
        //     } else {
        //       console.error("一般的なエラーは:", err);
        //     }
        //   } finally {
        //     setIsLoading(false);
        //   }
        // };
        // fecheTransactions();
    }, []);

    const topImgLogoStyle = {
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
                            src={topLogo}
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
