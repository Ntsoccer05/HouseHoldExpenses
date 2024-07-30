import { Avatar, Box, IconButton, Typography } from "@mui/material";
import React from "react";

interface AppTitleProps {
    title: string;
    icon?: JSX.Element;
}
function AppTitle({ title, icon }: AppTitleProps) {
    return (
        <Box>
            <Typography variant="h5" component="h1">
                {title}
            </Typography>
            <Avatar sx={{ margin: "8px auto", bgcolor: "secondary.main" }}>
                {icon}
            </Avatar>
        </Box>
    );
}

export default AppTitle;
