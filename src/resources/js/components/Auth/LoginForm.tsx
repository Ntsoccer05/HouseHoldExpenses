import React, { useState } from "react";
import axios from "axios";
import { LoginScheme, loginSchema } from "../../validations/Login";
import { zodResolver } from "@hookform/resolvers/zod";
import { Controller, SubmitHandler, useForm } from "react-hook-form";
import {
    Box,
    Button,
    ButtonGroup,
    FormControl,
    FormHelperText,
    IconButton,
    InputLabel,
    ListItemIcon,
    MenuItem,
    Stack,
    TextField,
    Typography,
} from "@mui/material";
import { useNavigate } from "react-router-dom";

function LoginForm() {
    type LoginInput = {
        email: string;
        password: string;
    };

    const [loginInput, setLogin] = useState<LoginInput>({
        email: "",
        password: "",
    });

    const navigate = useNavigate();

    const {
        control,
        setValue,
        watch,
        // errorsにバリデーションメッセージが格納される
        formState: { errors },
        handleSubmit,
        reset,
    } = useForm<LoginScheme>({
        // フォームの初期値設定
        defaultValues: {
            email: "",
            password: "",
        },
        // resolver: zodResolver()でバリデーション設定
        resolver: zodResolver(loginSchema),
    });

    const [isLoading, setIsLoading] = useState<boolean>(false);

    const loginSubmit: SubmitHandler<LoginScheme> = (data) => {
        //フォームデータ送信時に画面を再更新しないようにする処理
        setIsLoading(true);

        axios
            .post("/api/login", data)
            .then((response) => {
                // handleEmailVerification(response.data.token);
                // 送信成功時の処理
                setIsLoading(false);
                navigate("/");
                console.log(response.data);
            })
            .catch(function (error) {
                // 送信失敗時の処理
                console.log("通信に失敗しました");
            });
    };

    const formContent = (
        <>
            <Box component={"form"} onSubmit={handleSubmit(loginSubmit)}>
                <Stack spacing={2}>
                    {/* メールアドレス */}
                    <Controller
                        name="email"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                error={!!errors.email}
                                helperText={errors.email?.message}
                                {...field}
                                label="メールアドレス"
                                type="email"
                            />
                        )}
                    />
                    {/* パスワード */}
                    <Controller
                        name="password"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                error={!!errors.password}
                                helperText={errors.password?.message}
                                {...field}
                                label="パスワード"
                                type="password"
                            />
                        )}
                    />
                    {/* 保存ボタン */}
                    <Button type="submit" variant="contained" fullWidth>
                        ログイン
                    </Button>
                </Stack>
            </Box>
        </>
    );
    return (
        <>
            <Box
                sx={{
                    width: "100%",
                    height: "100%",
                    bgcolor: "background.paper",
                    boxSizing: "border-box", // ボーダーとパディングをwidthに含める
                    boxShadow: "0px 0px 15px -5px #777777",
                }}
            >
                {formContent}
            </Box>
        </>
    );
}

export default LoginForm;
