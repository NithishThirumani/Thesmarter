import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
import Button from "../components/Button";

export default function Topbar({ activeLabel, user, onRequestSignOut, signOutBusy, isMobile, onMenuClick }) {
  const { tokens: t, mode, toggleMode } = useTheme();

  return (
    <div
      style={{
        minHeight: 60,
        background: t.topbarBg,
        borderBottom: "none",
        boxShadow: mode === "light" ? "inset 0 -1px 0 rgba(15, 23, 42, 0.08)" : "inset 0 -1px 0 rgba(255, 255, 255, 0.06)",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        gap: 12,
        padding: isMobile ? "10px 14px" : "0 28px",
        position: "sticky",
        top: 0,
        zIndex: 50,
        fontFamily: TYPE.fontBody,
        flexWrap: "wrap",
      }}
    >
      <div style={{ display: "flex", alignItems: "center", gap: 12, minWidth: 0, flex: isMobile ? "1 1 auto" : "0 1 auto" }}>
        {isMobile && (
          <button
            type="button"
            aria-label="Open navigation menu"
            onClick={onMenuClick}
            style={{
              flexShrink: 0,
              width: 40,
              height: 40,
              borderRadius: 8,
              border: `1px solid ${t.border}`,
              background: t.bgElevated,
              color: t.text,
              fontSize: 20,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              lineHeight: 1,
            }}
          >
            ☰
          </button>
        )}
        <div
          style={{
            fontSize: isMobile ? TYPE.base : TYPE.md,
            fontWeight: TYPE.semibold,
            color: t.text,
            letterSpacing: "-0.01em",
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
            minWidth: 0,
          }}
        >
          {activeLabel}
        </div>
      </div>

      <div style={{ display: "flex", alignItems: "center", gap: isMobile ? 8 : 20, flexShrink: 0, marginLeft: isMobile ? undefined : "auto" }}>
        <button
          type="button"
          onClick={toggleMode}
          title={mode === "dark" ? "Switch to light theme" : "Switch to dark theme"}
          aria-label={mode === "dark" ? "Switch to light theme" : "Switch to dark theme"}
          style={{
            background: t.bgElevated,
            border: `1px solid ${t.border}`,
            borderRadius: 8,
            padding: "7px 11px",
            cursor: "pointer",
            color: t.text,
            fontSize: 18,
            lineHeight: 1,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          {mode === "dark" ? "☀️" : "🌙"}
        </button>

        {!isMobile && (
          <>
            <div style={{ width: 1, height: 22, background: t.border }} />
            <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
              <div
                style={{
                  width: 32,
                  height: 32,
                  borderRadius: "50%",
                  background: t.accentSubtle,
                  border: `1.5px solid ${t.accent}60`,
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  fontSize: TYPE.sm,
                  fontWeight: TYPE.bold,
                  color: t.accent,
                }}
              >
                {user?.name?.[0] || "A"}
              </div>
              <div style={{ maxWidth: 160 }}>
                <div style={{ fontSize: TYPE.sm, color: t.text, fontWeight: TYPE.semibold, lineHeight: 1.3 }}>{user?.name}</div>
                <div style={{ fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1 }}>{user?.role}</div>
              </div>
            </div>
          </>
        )}

        <Button variant="ghost" size="sm" onClick={() => onRequestSignOut()} disabled={signOutBusy}>
          {signOutBusy ? "Signing out…" : "Sign out"}
        </Button>
      </div>
    </div>
  );
}
