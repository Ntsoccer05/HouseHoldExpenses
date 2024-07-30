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
import React, { CSSProperties } from "react";

import HomeIcon from "@mui/icons-material/Home";
import EqualizerIcon from "@mui/icons-material/Equalizer";
import { NavLink } from "react-router-dom";
import LockOutlinedIcon from "@mui/icons-material/LockOutlined";
import MeetingRoomIcon from "@mui/icons-material/MeetingRoom";

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
    const MenuItems: menuItem[] = [
        { text: "トップ", path: "/", icon: HomeIcon },
        { text: "レポート", path: "/report", icon: EqualizerIcon },
        { text: "ログイン", path: "/login", icon: LockOutlinedIcon },
        { text: "サインイン", path: "/register", icon: MeetingRoomIcon },
    ];
    const NotLoginMenuItems: menuItem[] = [
        { text: "トップ", path: "/", icon: HomeIcon },
        { text: "ログイン", path: "/login", icon: HomeIcon },
        { text: "サインイン", path: "/register", icon: MeetingRoomIcon },
    ];

    const baseLinkStyle: CSSProperties = {
        textDecoration: "none",
        color: "inherit",
        display: "block",
    };

    const activeLinkStyle: CSSProperties = {
        backgroundColor: "rgba(0, 0, 0, 0.08)",
    };

    const drawer = (
        <div>
            <Toolbar />
            <Divider />
            <List>
                {MenuItems.map((item, index) => (
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
        </Box>
    );
};

export default Sidebar;
