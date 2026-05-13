import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function Toast({ toasts }) {
    const { tokens: t } = useTheme();
    const borderMap = { success: t.success, error: t.danger, info: t.accent, warning: t.warning };
    return (
        <div style={{ position: "fixed", bottom: 24, right: 24, zIndex: 2000, display: "flex", flexDirection: "column", gap: 8 }}>
            {toasts.map(toast => (
                <div key={toast.id} style={{
                    background: t.bgCard,
                    border: `1px solid ${t.borderStrong}`,
                    borderLeft: `3px solid ${borderMap[toast.type] || t.accent}`,
                    borderRadius: 8, padding: "13px 18px", minWidth: 270, maxWidth: 360,
                    boxShadow: "0 10px 40px rgba(0,0,0,0.7)",
                    animation: "slideInRight 0.22s ease",
                    fontFamily: TYPE.fontBody,
                }}>
                    <div style={{ fontWeight: TYPE.semibold, fontSize: TYPE.base, color: t.text, marginBottom: 2 }}>{toast.title}</div>
                    {toast.msg && <div style={{ fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.5 }}>{toast.msg}</div>}
                </div>
            ))}
        </div>
    );
};