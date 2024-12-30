import React from "react";
import { Provider } from "../types";

const useSocialLogin = () => {
    const socialLogin = async (
        provider: Provider,
        socialResponse: queryString.ParsedQuery<string>
    ) => {
        try {
            const { data } = await axios.post(
                `/api/login/${provider}/callback`,
                {
                    code: socialResponse.code,
                }
            );
            return data;
        } catch (e) {
            console.log(e);
            return false;
        }
    };
    return socialLogin;
};

export default useSocialLogin;
