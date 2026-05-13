import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function EmptyState({ icon = "📭", title, message }) {
    const { tokens: t } = useTheme();
    return (
      <div style={{ textAlign: "center", padding: "64px 20px" }}>
        <div style={{ fontSize: 40, marginBottom: 16, opacity: 0.5 }}>{icon}</div>
        <div style={{ fontWeight: TYPE.bold, fontSize: TYPE.lg, color: t.text, marginBottom: 8, fontFamily: TYPE.fontDisplay }}>{title}</div>
        <div style={{ fontSize: TYPE.base, color: t.textMuted, fontFamily: TYPE.fontBody, lineHeight: 1.6 }}>{message}</div>
      </div>
    );
  };