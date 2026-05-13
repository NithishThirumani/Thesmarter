import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function Badge({ status, children }) {
  const { tokens: t } = useTheme();
  const STATUS_COLORS = {
    Active: t.success,
    Trial: t.warning,
    Suspended: t.danger,
    Starter: t.textMuted,
    Pro: t.accent,
    Enterprise: t.info,
  };
  const color = STATUS_COLORS[status] || t.textSecondary;
  const label = children ?? status ?? "";
  return (
    <span
      style={{
        display: "inline-block",
        padding: "2px 8px",
        borderRadius: 6,
        fontSize: TYPE.xs,
        fontWeight: TYPE.semibold,
        background: `${color}20`,
        color,
        border: `1px solid ${color}40`,
      }}
    >
      {label}
    </span>
  );
}
