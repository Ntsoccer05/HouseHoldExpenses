import {
    Box,
    Divider,
    Drawer,
    List,
    ListItem,
    ListItemButton,
    ListItemIcon,
    ListItemText,
    Toolbar,
} from "@mui/material";
import React, { CSSProperties, useEffect, useState } from "react";

import HomeIcon from "@mui/icons-material/Home";
import EqualizerIcon from "@mui/icons-material/Equalizer";
import { NavLink } from "react-router-dom";
import LockOutlinedIcon from "@mui/icons-material/LockOutlined";
import LockOpenIcon from "@mui/icons-material/LockOpen";
import MeetingRoomIcon from "@mui/icons-material/MeetingRoom";
import { useAppContext } from "../../context/AppContext";
import axios from "axios";
import ModalComponent from "./ModalComponent";

interface SidebarProps {
    drawerWidth: number;
    mobileOpen: boolean;
    handleDrawerToggle: () => void;
}

interface menuItem {
    text: string;
    path: string;
    icon: React.ComponentType;
}

const Sidebar = ({
    drawerWidth,
    mobileOpen,
    handleDrawerToggle,
}: SidebarProps) => {
    const { LoginUser, setLoginUser } = useAppContext();

    const MenuItems: menuItem[] = [
        { text: "トップ", path: "/", icon: HomeIcon },
        { text: "レポート", path: "/report", icon: EqualizerIcon },
    ];

    const LogoutItems = [{ text: "ログアウト", icon: LockOutlinedIcon }];

    const NotLoginMenuItems: menuItem[] = [
        { text: "トップ", path: "/", icon: HomeIcon },
        { text: "ログイン", path: "/login", icon: MeetingRoomIcon },
        { text: "サインイン", path: "/register", icon: LockOpenIcon },
    ];

    const [menuItems, setMenuItems] = useState<menuItem[]>(MenuItems);

    const objToArray = (obj: menuItem[]) => {
        return Object.values(obj);
    };
    const [showModal, setShowModal] = useState<boolean>(false);
    const [modalMainMessage, setModalMainMessage] = useState<string>("");
    const [modalMessage, setModalMessage] = useState<string>("");
    const [modalOption, setModalOption] = useState<number>(0);

    useEffect(() => {
        LoginUser
            ? setMenuItems((beforeData) => {
                  const menusList = MenuItems;
                  return objToArray(menusList);
              })
            : setMenuItems((beforeData) => {
                  const menusList = { ...beforeData, ...NotLoginMenuItems };
                  return objToArray(menusList);
              });
    }, [LoginUser]);

    const baseLinkStyle: CSSProperties = {
        textDecoration: "none",
        color: "inherit",
        display: "block",
    };

    const activeLinkStyle: CSSProperties = {
        backgroundColor: "rgba(0, 0, 0, 0.08)",
    };

    const clickLogout = () => {
        setShowModal(true);
        setModalMainMessage("ログアウト");
        setModalMessage("ログアウトしますか？");
        setModalOption(1);
    };

    const logout = async () => {
        await axios
            .post("/api/logout", LoginUser)
            .then((res) => {
                setLoginUser(undefined);
                setModalMainMessage("ログアウト完了");
                setModalMessage("ログアウトしました");
                setModalOption(0);
            })
            .catch((err) => {
                handleCloseModal();
            });
    };

    const handleCloseModal: () => void = () => {
        setShowModal(false);
    };

    const drawer = (
        <div>
            <Toolbar />
            <Divider />
            <List>
                {menuItems.map((item, index) => (
                    <NavLink
                        key={item.text}
                        to={item.path}
                        style={({ isActive }) => {
                            return {
                                ...baseLinkStyle,
                                ...(isActive ? activeLinkStyle : {}),
                            };
                        }}
                    >
                        <ListItem key={index} disablePadding>
                            <ListItemButton>
                                <ListItemIcon>
                                    <item.icon />
                                </ListItemIcon>
                                <ListItemText primary={item.text} />
                            </ListItemButton>
                        </ListItem>
                    </NavLink>
                ))}
                {LoginUser &&
                    LogoutItems.map((item, index) => (
                        <NavLink
                            key={item.text}
                            onClick={clickLogout}
                            to=""
                            style={({ isActive }) => {
                                return {
                                    ...baseLinkStyle,
                                    ...(isActive ? activeLinkStyle : {}),
                                };
                            }}
                        >
                            <ListItem key={index} disablePadding>
                                <ListItemButton>
                                    <ListItemIcon>
                                        <item.icon />
                                    </ListItemIcon>
                                    <ListItemText primary={item.text} />
                                </ListItemButton>
                            </ListItem>
                        </NavLink>
                    ))}
            </List>
        </div>
    );

    return (
        <Box
            component="nav"
            sx={{ width: { md: drawerWidth }, flexShrink: { md: 0 } }}
            aria-label="mailbox folders"
        >
            {/* モバイル用 */}
            <Drawer
                variant="temporary"
                open={mobileOpen}
                onClose={handleDrawerToggle}
                ModalProps={{
                    keepMounted: true, // Better open performance on mobile.
                }}
                sx={{
                    display: { xs: "block", md: "none" },
                    "& .MuiDrawer-paper": {
                        boxSizing: "border-box",
                        width: drawerWidth,
                    },
                }}
            >
                {drawer}
            </Drawer>

            {/* PC用 */}
            <Drawer
                variant="permanent"
                sx={{
                    display: { xs: "none", md: "block" },
                    "& .MuiDrawer-paper": {
                        boxSizing: "border-box",
                        width: drawerWidth,
                    },
                }}
                open
            >
                {drawer}
            </Drawer>
            <ModalComponent
                showModal={showModal}
                mainMessage={modalMainMessage}
                contentMessage={modalMessage}
                modalOption={modalOption}
                handleCloseModal={handleCloseModal}
                handleFunc={logout}
            />
        </Box>
    );
};

export default Sidebar;
