import React, { useState } from "react";
import axios from "axios";
import { RegisterScheme, registerSchema } from "../../validations/Register";
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
    Select,
    Stack,
    TextField,
    Typography,
} from "@mui/material";
import { RegisterError } from "../../utils/errorHandling";

function RegisterForm() {
    type RegisterInput = {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    };

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
                setIsLoading(false);
                console.log(response.data);
            })
            .catch(function (error) {
                const errorResMsgs = error.response.data.errors;
                RegisterError(errorResMsgs, setErrorMsgs);
                // 送信失敗時の処理
                console.log("通信に失敗しました");
            });
    };

    const formContent = (
        <>
            <Box component={"form"} onSubmit={handleSubmit(registerSubmit)}>
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
                                label="ユーザー名"
                                type="text"
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
                            />
                        )}
                    />
                    {/* 保存ボタン */}
                    <Button type="submit" variant="contained" fullWidth>
                        登録
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

//     return (
//         <div className="w-96 ml-auto mr-auto">
//             <div className="block w-full mt-10 mb-10 p-2 rounded-3xl bg-slate-200">
//                 <h1 className="w-full border-b-2 text-center text-2xl mt-10 mb-10 font-bold">
//                     新規登録
//                 </h1>

//                 <div>{isLoading ? "送信中...." : ""}</div>

//                 <form onSubmit={registerSubmit}>
//                     <span className="block w-full mt-4">名前</span>
//                     <span className="block text-red-500">
//                         {responseData.error_name}
//                     </span>
//                     <input
//                         type="text"
//                         name="name"
//                         value={registerInput.name}
//                         onChange={handleInput}
//                         className="block w-full h-10 border border-gray-600 rounded"
//                     />

//                     <span className="block w-full mt-4">メールアドレス</span>
//                     <span className="block text-red-500">
//                         {responseData.error_email}
//                     </span>
//                     <input
//                         type="email"
//                         name="email"
//                         value={registerInput.email}
//                         onChange={handleInput}
//                         className="block w-full h-10 border border-gray-600 rounded"
//                     />

//                     <span className="block w-full mt-4">パスワード</span>
//                     <span className="block text-rose-600">
//                         {responseData.error_password}
//                     </span>
//                     <input
//                         type="password"
//                         name="password"
//                         value={registerInput.password}
//                         onChange={handleInput}
//                         className="block w-full h-10 border border-gray-600 rounded"
//                     />

//                     <span className="block w-full mt-4">
//                         パスワード(確認でもう一度入力)
//                     </span>
//                     <input
//                         type="password"
//                         name="password_confirmation"
//                         value={registerInput.password_confirmation}
//                         onChange={handleInput}
//                         className="block w-full h-10 border border-gray-600 rounded"
//                     />

//                     <button
//                         type="submit"
//                         className="block mt-10 mb-10 bg-amber-500 w-full h-10 text-white ml-auto mr-auto rounded-lg shadow-lg font-medium text-1xl"
//                     >
//                         登録
//                     </button>
//                 </form>
//             </div>
//         </div>
//     );
// }

export default RegisterForm;
