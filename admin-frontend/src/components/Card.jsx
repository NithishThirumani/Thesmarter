import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function Card({ children, style = {}, title, action }) {
    const { tokens: t } = useTheme();
    return (
      <div style={{
        background: t.bgCard,
        border: `1px solid ${t.border}`,
        borderRadius: 10, overflow: "hidden", ...style
      }}>
        {title && (
          <div style={{
            padding: "14px 20px", borderBottom: `1px solid ${t.border}`,
            display: "flex", justifyContent: "space-between", alignItems: "center",
            background: t.bgElevated,
          }}>
            <span style={{
              fontWeight: TYPE.semibold, fontSize: TYPE.base, color: t.text,
              letterSpacing: "0.01em", fontFamily: TYPE.fontDisplay
            }}>{title}</span>
            {action}
          </div>
        )}
        <div style={{ padding: 20 }}>{children}</div>
      </div>
    );
  };