import React from "react";
import ReactDOM from "react-dom/client";
import { ThemeProvider } from "./theme";

function showError(message, detail) {
  const el = document.getElementById("root");
  if (el) {
    const escaped = (s) => (s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    el.innerHTML =
      '<div style="min-height:100vh;padding:24px;background:#07080a;color:#f05252;font-family:system-ui,sans-serif;">' +
      '<h2 style="margin:0 0 12px">Failed to load app</h2>' +
      '<p style="margin:0 0 8px;color:#eef0f3">' +
      escaped(message) +
      "</p>" +
      (detail ? '<pre style="font-size:12px;overflow:auto;color:#8b92a0">' + escaped(detail) + "</pre>" : "") +
      "</div>";
  }
}

let Main;

import("./main.jsx")
  .then((m) => {
    Main = m.default;
    const rootEl = document.getElementById("root");
    if (!rootEl) {
      document.body.innerHTML = "<div style='padding:24px;color:red;'>No #root element</div>";
      return;
    }
    const root = ReactDOM.createRoot(rootEl);
    root.render(
      <React.StrictMode>
        <ThemeProvider>
          <Main />
        </ThemeProvider>
      </React.StrictMode>
    );
  })
  .catch((e) => {
    showError(e?.message || "Unknown error", e?.stack);
    console.error(e);
  });
