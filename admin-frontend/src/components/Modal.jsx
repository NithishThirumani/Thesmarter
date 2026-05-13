import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
    
export default function Modal({ open, onClose, title, children, width = 540 }) {
    const { tokens: t } = useTheme();
    if (!open) return null;
    return (
      <div style={{
        position: "fixed", inset: 0, background: "rgba(0,0,0,0.75)", zIndex: 1000,
        display: "flex", alignItems: "center", justifyContent: "center", backdropFilter: "blur(6px)"
      }} onClick={onClose}>
        <div style={{
          background: t.bgCard, border: `1px solid ${t.borderStrong}`,
          borderRadius: 10, width, maxWidth: "95vw", maxHeight: "88vh", overflow: "auto",
          boxShadow: "0 40px 100px rgba(0,0,0,0.9), 0 0 0 1px rgba(232,197,71,0.06)",
        }} onClick={e => e.stopPropagation()}>
          <div style={{
            padding: "18px 24px", borderBottom: `1px solid ${t.border}`,
            display: "flex", justifyContent: "space-between", alignItems: "center"
          }}>
            <span style={{ fontWeight: TYPE.bold, fontSize: TYPE.lg, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: "-0.01em" }}>{title}</span>
            <button onClick={onClose} style={{
              background: t.bgHover, border: `1px solid ${t.border}`, color: t.textMuted,
              cursor: "pointer", fontSize: 16, lineHeight: 1, width: 28, height: 28,
              borderRadius: 6, display: "flex", alignItems: "center", justifyContent: "center"
            }}>×</button>
          </div>
          <div style={{ padding: "24px" }}>{children}</div>
        </div>
      </div>
    );
  }