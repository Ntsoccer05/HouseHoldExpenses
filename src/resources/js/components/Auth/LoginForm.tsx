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
import { LoginError } from "../../utils/errorHandling";
import Modal from "@mui/material/Modal";
import ModalComponent from "../common/ModalComponent";

function LoginForm() {
    type LoginInput = {
        email: string;
        password: string;
    };

    type LoginErrMsgs = {
        emailErrMsg?: string;
        passErrMsg?: string;
    };

    type ResendConfirmEmail = {
        email: string;
    };

    const [errorMsgs, setErrorMsgs] = useState<LoginErrMsgs>({
        emailErrMsg: "",
        passErrMsg: "",
    });

    const [loginInput, setLogin] = useState<LoginInput>({
        email: "",
        password: "",
    });

    const [reConfirmEmail, setReConfirmEmail] = useState<boolean>(false);

    const [showModal, setShowModal] = useState<boolean>(false);
    const [modalMainMessage, setModalMainMessage] = useState<string>("");
    const [modalMessage, setModalMessage] = useState<string>("");

    const navigate = useNavigate();

    const {
        control,
        setValue,
        watch,
        // errorsにバリデーションメッセージが格納される
        formState: { errors },
        handleSubmit,
        reset,
        getValues,
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
        axios
            .post("/api/login", data)
            .then((response) => {
                // handleEmailVerification(response.data.token);
                // 送信成功時の処理
                navigate("/");
                console.log(response.data);
            })
            .catch(function (error) {
                // 送信失敗時の処理
                if (error.response.status == 403) {
                    setErrorMsgs((state) => {
                        return {
                            ...state,
                            emailErrMsg: error.response.data.error,
                        };
                    });
                    setReConfirmEmail(true);
                }
                const errorResMsgs = error.response.data.errors;
                LoginError(errorResMsgs, setErrorMsgs);
                console.log("通信に失敗しました");
            });
    };

    const resendConfirmEmail: () => void = async () => {
        //フォームデータ送信時に画面を再更新しないようにする処理
        setIsLoading(true);
        const formEmailVal = getValues("email");
        setModalMainMessage("認証用メール");
        setModalMessage("認証用メールを送信しました。ご確認お願いします。");
        await axios
            .post("/api/email/verification-notification", {
                email: formEmailVal,
            })
            .then((res) => {
                setShowModal(true);
                setIsLoading(false);
            })
            .catch((err) => {
                console.log(err);
            });
    };

    const handleCloseModal: () => void = () => {
        setShowModal(false);
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
                                helperText={errorMsgs?.emailErrMsg}
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
                                helperText={errorMsgs?.passErrMsg}
                                {...field}
                                label="パスワード"
                                type="password"
                            />
                        )}
                    />
                    {/* 保存ボタン */}
                    {/* イベントハンドラで条件式の場合onClick={()=>}のようにアロー関数型で記述する */}
                    <Button
                        type="submit"
                        variant="contained"
                        fullWidth
                        disabled={isLoading}
                        onClick={() =>
                            reConfirmEmail ? resendConfirmEmail() : undefined
                        }
                    >
                        {reConfirmEmail
                            ? isLoading
                                ? "認証メール送信中"
                                : "再度メール認証"
                            : "ログイン"}
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
            <ModalComponent
                showModal={showModal}
                mainMessage={modalMainMessage}
                contentMessage={modalMessage}
                handleCloseModal={handleCloseModal}
            />
        </>
    );
}

export default LoginForm;
