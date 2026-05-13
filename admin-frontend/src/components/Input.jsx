import React, { useState } from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";



export default function Input({ label, value, onChange, placeholder, type = "text", icon, hint, style = {}, disabled = false }) {
    const { tokens: t } = useTheme();
    const [focus, setFocus] = useState(false);
    return (
      <div style={{ display: "flex", flexDirection: "column", gap: 6, ...style }}>
        {label && (
          <label style={{
            fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary,
            letterSpacing: "0.06em", textTransform: "uppercase", fontFamily: TYPE.fontBody
          }}>{label}</label>
        )}
        <div style={{ position: "relative" }}>
          {icon && (
            <span style={{ position: "absolute", left: 12, top: "50%", transform: "translateY(-50%)", color: t.textMuted, fontSize: 15, pointerEvents: "none" }}>{icon}</span>
          )}
          <input
            type={type} value={value} onChange={e => onChange(e.target.value)} placeholder={placeholder}
            onFocus={() => setFocus(true)} onBlur={() => setFocus(false)} disabled={disabled}
            style={{
              width: "100%", height: 38,
              padding: `0 12px 0 ${icon ? "38px" : "12px"}`,
              background: t.bgElevated,
              border: `1px solid ${focus ? t.accent : t.border}`,
              boxShadow: focus ? `0 0 0 3px ${t.accent}18` : "none",
              borderRadius: 6, color: t.text, fontSize: TYPE.base,
              fontFamily: TYPE.fontBody, outline: "none",
              transition: "border-color 0.18s, box-shadow 0.18s",
              boxSizing: "border-box",
              opacity: disabled ? 0.65 : 1,
              cursor: disabled ? "not-allowed" : "text",
            }}
          />
        </div>
        {hint && <span style={{ fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontBody }}>{hint}</span>}
      </div>
    );
  };