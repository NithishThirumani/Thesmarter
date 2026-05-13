import React from "react";
import { NavLink } from "react-router-dom";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
import { NAV_ITEMS } from "../theSmartr/";

export default function Sidebar({ collapsed, setCollapsed, user, isMobile, mobileNavOpen, onCloseMobile }) {
  const { tokens: t } = useTheme();
  const effectiveCollapsed = collapsed && !isMobile;
  const sidebarW = isMobile ? Math.min(280, typeof window !== "undefined" ? window.innerWidth * 0.88 : 260) : effectiveCollapsed ? 64 : 228;
  const groups = [...new Set(NAV_ITEMS.map((n) => n.group))];

  const shellStyle = {
    width: isMobile ? sidebarW : effectiveCollapsed ? 64 : 228,
    background: t.sidebarBg,
    borderRight: `1px solid ${t.border}`,
    height: "100vh",
    display: "flex",
    flexDirection: "column",
    transition: "width 0.22s ease, transform 0.25s ease",
    position: "fixed",
    left: 0,
    top: 0,
    zIndex: isMobile ? 160 : 100,
    overflow: "hidden",
    fontFamily: TYPE.fontBody,
    ...(isMobile && {
      width: sidebarW,
      maxWidth: "88vw",
      boxShadow: mobileNavOpen ? "8px 0 32px rgba(0,0,0,0.2)" : "none",
      transform: mobileNavOpen ? "translateX(0)" : "translateX(-100%)",
    }),
  };

  return (
    <>
      <style>{`
        a.sidebar-nav-link, a.sidebar-nav-link:visited { color: inherit !important; }
      `}</style>
      <div style={shellStyle}>
        {/* Logo */}
        <div
          style={{
            padding: effectiveCollapsed ? "0 16px" : "0 20px",
            height: 60,
            display: "flex",
            alignItems: "center",
            gap: 10,
            borderBottom: `1px solid ${t.border}`,
            flexShrink: 0,
          }}
        >
          <div
            style={{
              width: 30,
              height: 30,
              background: t.accent,
              borderRadius: 7,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              flexShrink: 0,
              boxShadow: `0 4px 12px ${t.accent}40`,
            }}
          >
            <span style={{ fontSize: 15, fontWeight: 900, color: "#000", fontFamily: TYPE.fontDisplay }}>S</span>
          </div>
          {!effectiveCollapsed && (
            <div style={{ minWidth: 0 }}>
              <div
                style={{
                  fontFamily: TYPE.fontDisplay,
                  fontSize: 16,
                  fontWeight: TYPE.black,
                  color: t.text,
                  letterSpacing: "-0.03em",
                  lineHeight: 1.2,
                }}
              >
                theSmartr
              </div>
              <div
                style={{
                  fontSize: 9,
                  color: t.textMuted,
                  letterSpacing: "0.08em",
                  textTransform: "uppercase",
                  fontWeight: TYPE.medium,
                }}
              >
                Admin Console
              </div>
            </div>
          )}
          {isMobile && (
            <button
              type="button"
              aria-label="Close menu"
              onClick={onCloseMobile}
              style={{
                marginLeft: "auto",
                background: t.bgHover,
                border: `1px solid ${t.border}`,
                color: t.textMuted,
                borderRadius: 8,
                width: 34,
                height: 34,
                cursor: "pointer",
                fontSize: 18,
                lineHeight: 1,
                flexShrink: 0,
              }}
            >
              ×
            </button>
          )}
        </div>

        {/* Nav */}
        <nav style={{ flex: 1, overflowY: "auto", padding: "10px 0", overflowX: "hidden", color: t.textSecondary }}>
          {groups.map((group) => (
            <div key={group || "main"}>
              {group && !effectiveCollapsed && (
                <div
                  style={{
                    padding: "16px 20px 5px",
                    fontSize: TYPE.xs - 1,
                    fontWeight: TYPE.bold,
                    color: t.textMuted,
                    letterSpacing: "0.1em",
                    textTransform: "uppercase",
                  }}
                >
                  {group}
                </div>
              )}
              {group && effectiveCollapsed && <div style={{ height: 8 }} />}
              {NAV_ITEMS.filter(
                (n) =>
                  n.group === group && (!n.requireSuperAdmin || user?.role === "super_admin")
              ).map((item) => (
                <NavLink
                  key={item.key}
                  to={item.path}
                  end={item.path === "/dashboard"}
                  title={effectiveCollapsed ? item.label : undefined}
                  className="sidebar-nav-link"
                  onClick={() => isMobile && onCloseMobile?.()}
                  style={({ isActive }) => ({
                    display: "flex",
                    alignItems: "center",
                    gap: effectiveCollapsed ? 0 : 10,
                    padding: effectiveCollapsed ? "10px 0" : "10px 18px",
                    margin: effectiveCollapsed ? "1px 0" : "1px 8px",
                    justifyContent: effectiveCollapsed ? "center" : "flex-start",
                    background: isActive ? `${t.accent}18` : "transparent",
                    borderLeft:
                      !effectiveCollapsed && isActive
                        ? `3px solid ${t.accent}`
                        : !effectiveCollapsed
                          ? "3px solid transparent"
                          : "none",
                    borderRadius: effectiveCollapsed ? 0 : 8,
                    color: isActive ? t.accent : t.textSecondary,
                    cursor: "pointer",
                    transition: "all 0.15s ease",
                    fontSize: TYPE.base,
                    fontWeight: isActive ? TYPE.semibold : TYPE.medium,
                    textDecoration: "none",
                    outline: "none",
                  })}
                  onMouseEnter={(e) => {
                    const active = e.currentTarget.getAttribute("aria-current") === "page";
                    e.currentTarget.style.background = active ? `${t.accent}18` : t.bgHover;
                    e.currentTarget.style.color = active ? t.accent : t.text;
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.background = "";
                    e.currentTarget.style.color = "";
                  }}
                >
                  <span style={{ fontSize: 16, lineHeight: 1, flexShrink: 0, opacity: 0.9 }}>{item.icon}</span>
                  {!effectiveCollapsed && (
                    <span style={{ whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", fontSize: TYPE.base }}>
                      {item.label}
                    </span>
                  )}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        {/* Collapse */}
        <div style={{ borderTop: `1px solid ${t.border}`, padding: effectiveCollapsed ? "12px 0" : "12px 14px", flexShrink: 0 }}>
          {!isMobile && (
            <button
              type="button"
              onClick={() => setCollapsed(!collapsed)}
              style={{
                width: "100%",
                background: t.bgHover,
                border: `1px solid ${t.border}`,
                color: t.textMuted,
                cursor: "pointer",
                fontSize: 12,
                padding: "6px 0",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                borderRadius: 6,
                transition: "all 0.15s",
                fontFamily: TYPE.fontBody,
                gap: 6,
              }}
              onMouseEnter={(e) => (e.currentTarget.style.color = t.text)}
              onMouseLeave={(e) => (e.currentTarget.style.color = t.textMuted)}
            >
              {collapsed ? "→" : <>← Collapse</>}
            </button>
          )}
        </div>
      </div>
    </>
  );
}
