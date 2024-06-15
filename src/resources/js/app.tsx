import './bootstrap';

import React from "react";
import ReactDOM from "react-dom/client";
import Router from "./routes/router";

const root = ReactDOM.createRoot(
  document.getElementById("app") as HTMLElement
);
root.render(
  <React.StrictMode>
    <Router />
  </React.StrictMode>
);