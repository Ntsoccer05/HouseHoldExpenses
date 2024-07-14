import React, { useEffect } from "react";
import { useParams } from "react-router-dom";
import axios from "axios";

const VerifyEmail = () => {
    const { id, hash } = useParams();

    useEffect(() => {
        const verifyEmail = async () => {
            const token = localStorage.getItem("token"); // 認証トークンを取得
            try {
                const response = await axios.get(
                    `/api/email/verify/${id}/${hash}`
                );
                alert(response.data.message);
            } catch (error) {
                console.error("Error verifying email:", error);
                alert("Failed to verify email.");
            }
        };

        verifyEmail();
    }, [id, hash]);

    return <div>Verifying your email...</div>;
};

export default VerifyEmail;
