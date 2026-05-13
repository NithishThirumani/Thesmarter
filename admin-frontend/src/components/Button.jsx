import React, { useState } from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";


export default function Button({ children, variant = "primary", size = "md", onClick, icon, disabled, style = {}, type = "button" }) {
    const { tokens: t } = useTheme();
    const sizes = {
        sm: { padding: "5px 14px", fontSize: TYPE.sm, height: 30 },
        md: { padding: "7px 18px", fontSize: TYPE.base, height: 36 },
        lg: { padding: "10px 28px", fontSize: TYPE.md, height: 42 },
    };
    const variants = {
        primary: { bg: t.accent, color: "#000", border: "none", hoverBg: t.accentHover },
        secondary: { bg: t.bgElevated, color: t.text, border: `1px solid ${t.border}`, hoverBg: t.bgHover },
        ghost: { bg: "transparent", color: t.textSecondary, border: `1px solid ${t.border}`, hoverBg: t.bgHover },
        danger: { bg: "transparent", color: t.danger, border: `1px solid ${t.danger}40`, hoverBg: t.dangerBg },
        success: { bg: "transparent", color: t.success, border: `1px solid ${t.success}40`, hoverBg: t.successBg },
        dark: { bg: t.bgElevated, color: t.text, border: `1px solid ${t.border}`, hoverBg: t.bgHover },
    };
    const v = variants[variant] || variants.primary;
    const s = sizes[size] || sizes.md;
    const [hover, setHover] = useState(false);
    return (
        <button
            type={type}
            disabled={disabled}
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                padding: s.padding, height: s.height,
                background: hover ? v.hoverBg : v.bg, color: v.color,
                border: v.border, borderRadius: 6, cursor: disabled ? "not-allowed" : "pointer",
                fontFamily: TYPE.fontBody, fontWeight: TYPE.semibold, fontSize: s.fontSize,
                display: "inline-flex", alignItems: "center", gap: 6,
                transition: "all 0.15s ease", opacity: disabled ? 0.45 : 1,
                letterSpacing: "0.01em", whiteSpace: "nowrap", ...style
            }}
        >{icon && <span style={{ fontSize: s.fontSize + 1, lineHeight: 1 }}>{icon}</span>}{children}</button>
    );
};