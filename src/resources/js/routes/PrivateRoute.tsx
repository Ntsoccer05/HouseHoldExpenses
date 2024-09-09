import { ReactNode } from "react";
import { useAppContext } from "../context/AppContext";
import { Navigate } from "react-router-dom";

// カスタムコンポーネントは大文字で始める必要がある
const PrivateRoute = ({ children }: { children: ReactNode }) => {
    const { LoginUser } = useAppContext();
    return LoginUser ? children : <Navigate to="/login" />;
};

export default PrivateRoute;
