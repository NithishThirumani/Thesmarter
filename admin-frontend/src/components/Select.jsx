import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function Select({ label, value, onChange, options, disabled = false }) {
    const { tokens: t } = useTheme();
    return (
      <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
        {label && (
          <label style={{
            fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary,
            letterSpacing: "0.06em", textTransform: "uppercase", fontFamily: TYPE.fontBody
          }}>{label}</label>
        )}
        <select value={value} onChange={e => onChange(e.target.value)} disabled={disabled}
          style={{
            height: 38, padding: "0 12px",
            background: t.bgElevated, border: `1px solid ${t.border}`,
            borderRadius: 6, color: t.text, fontSize: TYPE.base,
            fontFamily: TYPE.fontBody, outline: "none", cursor: disabled ? "not-allowed" : "pointer",
            opacity: disabled ? 0.65 : 1,
            appearance: "none",
            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238b92a0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E")`,
            backgroundRepeat: "no-repeat", backgroundPosition: "right 12px center",
            paddingRight: 32,
          }}>
          {options.map(o => <option key={o.value} value={o.value} style={{ background: t.bgCard }}>{o.label}</option>)}
        </select>
      </div>
    );
  }