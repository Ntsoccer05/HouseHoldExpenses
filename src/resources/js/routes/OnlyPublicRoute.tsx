import { ReactNode } from "react";
import { useAppContext } from "../context/AppContext";
import { Navigate } from "react-router-dom";

// カスタムコンポーネントは大文字で始める必要がある
const OnlyPublicRoute = ({ children }: { children: ReactNode }) => {
    const { LoginUser, isLoading } = useAppContext();

    return LoginUser ? <Navigate to="/" /> : children;
};

export default OnlyPublicRoute;
