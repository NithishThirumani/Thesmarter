import React from "react";
import { useTheme } from "../theme";

export default function ToggleSwitch({ checked, onChange, disabled = false, ariaLabel = "toggle" }) {
  const { tokens: t } = useTheme();
  return (
    <button
      type="button"
      role="switch"
      aria-checked={!!checked}
      aria-label={ariaLabel}
      disabled={disabled}
      onClick={() => {
        if (!disabled) onChange?.(!checked);
      }}
      style={{
        width: 46,
        height: 24,
        borderRadius: 999,
        border: `1px solid ${checked ? t.accent : t.border}`,
        background: checked ? t.accent : t.bgElevated,
        position: "relative",
        transition: "all 0.15s ease",
        cursor: disabled ? "not-allowed" : "pointer",
        opacity: disabled ? 0.5 : 1,
      }}
    >
      <span
        style={{
          position: "absolute",
          top: 3,
          left: checked ? 23 : 3,
          width: 18,
          height: 18,
          borderRadius: "50%",
          background: checked ? "#000" : t.textMuted,
          transition: "all 0.15s ease",
          boxShadow: checked ? "0 8px 20px rgba(250,204,21,0.25)" : "none",
        }}
      />
    </button>
  );
}
