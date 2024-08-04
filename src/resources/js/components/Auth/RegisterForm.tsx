import React, { useState } from "react";
import axios from "axios";
import { RegisterScheme, registerSchema } from "../../validations/Register";
import { zodResolver } from "@hookform/resolvers/zod";
import { Controller, SubmitHandler, useForm } from "react-hook-form";
import { Box, Button, Container, Stack, TextField } from "@mui/material";
import { RegisterError } from "../../utils/errorHandling";
import ModalComponent from "../common/ModalComponent";
import MeetingRoomIcon from "@mui/icons-material/MeetingRoom";
import AppTitle from "../layout/AppTitle";

function RegisterForm() {
    type RegisterErrMsgs = {
        nameErrMsg?: string;
        emailErrMsg?: string;
        passErrMsg?: string;
        passConfErrMsg?: string;
    };

    const [errorMsgs, setErrorMsgs] = useState<RegisterErrMsgs>({
        nameErrMsg: "",
        emailErrMsg: "",
        passErrMsg: "",
        passConfErrMsg: "",
    });

    const [showModal, setShowModal] = useState<boolean>(false);
    const [modalMainMessage, setModalMainMessage] = useState<string>("");
    const [modalMessage, setModalMessage] = useState<string>("");

    const {
        control,
        // setValue,
        // watch,
        // errorsにバリデーションメッセージが格納される
        // formState: { errors },
        handleSubmit,
        // reset,
    } = useForm<RegisterScheme>({
        // フォームの初期値設定
        defaultValues: {
            name: "",
            email: "",
            password: "",
            password_confirmation: "",
        },
        // resolver: zodResolver()でバリデーション設定
        // resolver: zodResolver(registerSchema),
    });

    const [isLoading, setIsLoading] = useState<boolean>(false);

    const registerSubmit: SubmitHandler<RegisterScheme> = (data) => {
        //フォームデータ送信時に画面を再更新しないようにする処理
        setIsLoading(true);

        axios
            .post("/api/register", data)
            .then((response) => {
                // 送信成功時の処理
                setShowModal(true);
                setModalMainMessage("認証用メール");
                setModalMessage(
                    "認証用メールを送信しました。ご確認お願いします。"
                );
                setIsLoading(false);
                console.log(response.data);
            })
            .catch(function (error) {
                setIsLoading(false);
                debugger;
                const errorResMsgs = error.response.data.errors;
                RegisterError(errorResMsgs, setErrorMsgs);
                // 送信失敗時の処理
                console.log("通信に失敗しました");
            });
    };
    const handleCloseModal: () => void = () => {
        setShowModal(false);
    };
    const IconComponents: JSX.Element = <MeetingRoomIcon />;

    const formContent = (
        <>
            <Box
                component={"form"}
                sx={{ width: "100%" }}
                onSubmit={handleSubmit(registerSubmit)}
            >
                <Stack spacing={2}>
                    {/* ユーザー名 */}
                    <Controller
                        name="name"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                InputLabelProps={{
                                    shrink: true,
                                }}
                                // errors.nameは値が入っており、!!で値が入っている場合True、ない場合はFalseと変換している
                                error={!!errorMsgs?.nameErrMsg}
                                helperText={errorMsgs?.nameErrMsg}
                                {...field}
                                autoFocus
                                label="ユーザー名"
                                type="text"
                                required
                            />
                        )}
                    />
                    {/* メールアドレス */}
                    <Controller
                        name="email"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                error={!!errorMsgs?.emailErrMsg}
                                helperText={errorMsgs?.emailErrMsg}
                                {...field}
                                label="メールアドレス"
                                type="email"
                                required
                            />
                        )}
                    />
                    {/* パスワード */}
                    <Controller
                        name="password"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                error={!!errorMsgs?.passErrMsg}
                                helperText={errorMsgs?.passErrMsg}
                                {...field}
                                label="パスワード"
                                type="password"
                                required
                            />
                        )}
                    />
                    {/* パスワード確認 */}
                    <Controller
                        name="password_confirmation"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                error={!!errorMsgs?.passConfErrMsg}
                                helperText={errorMsgs?.passConfErrMsg}
                                {...field}
                                label="パスワード確認"
                                type="password"
                                required
                            />
                        )}
                    />
                    {/* 保存ボタン */}
                    <Button
                        type="submit"
                        variant="contained"
                        fullWidth
                        disabled={isLoading}
                    >
                        {isLoading ? "認証メール送信中" : "登録"}
                    </Button>
                </Stack>
            </Box>
        </>
    );
    return (
        <>
            <Container component="main" maxWidth="xs">
                <Box
                    sx={{
                        marginTop: 8,
                        display: "flex",
                        flexDirection: "column",
                        alignItems: "center",
                    }}
                >
                    <AppTitle
                        title="サインイン"
                        icon={IconComponents}
                    ></AppTitle>
                    {formContent}
                    <ModalComponent
                        showModal={showModal}
                        mainMessage={modalMainMessage}
                        contentMessage={modalMessage}
                        handleCloseModal={handleCloseModal}
                    />
                </Box>
            </Container>
        </>
    );
}

export default RegisterForm;
