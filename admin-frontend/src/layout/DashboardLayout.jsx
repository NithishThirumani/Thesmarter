/**
 * Protected layout: Sidebar + Topbar + main content area. Used for all authenticated routes.
 */
import React, { useState, useCallback } from "react";
import { Outlet, useLocation, useNavigate } from "react-router-dom";
import Sidebar from "./Sidebar";
import Topbar from "./Topbar";
import Toast from "../components/Toast";
import Modal from "../components/Modal";
import Button from "../components/Button";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
import { useMediaQuery } from "../hooks/useMediaQuery";
import { useAuth } from "../auth/authContext";
import { NAV_ITEMS } from "../theSmartr";
import { PATH_LABELS } from "../routes";

export default function DashboardLayout() {
  const { tokens: t, mode } = useTheme();
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const isMobile = useMediaQuery("(max-width: 900px)");

  const [collapsed, setCollapsed] = useState(false);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [signOutBusy, setSignOutBusy] = useState(false);
  const [signOutConfirmOpen, setSignOutConfirmOpen] = useState(false);
  const [toasts, setToasts] = useState([]);

  const addToast = useCallback((type, title, msg) => {
    const id = Date.now();
    setToasts((prev) => [...prev, { id, type, title, msg }]);
    setTimeout(() => setToasts((prev) => prev.filter((x) => x.id !== id)), 4000);
  }, []);

  const handleLogout = async () => {
    if (signOutBusy) return;
    setSignOutBusy(true);
    try {
      await logout();
      navigate("/login", { replace: true });
    } catch (e) {
      addToast("error", "Sign out failed", e?.message || "Please try again.");
      setSignOutBusy(false);
    }
  };

  const beginSignOutAfterConfirm = () => {
    setSignOutConfirmOpen(false);
    void handleLogout();
  };

  const pathname = location.pathname;
  const candidates = NAV_ITEMS.filter((n) => n.path && (pathname === n.path || pathname.startsWith(n.path + "/")));
  const activeItem = candidates.length
    ? candidates.sort((a, b) => b.path.length - a.path.length)[0]
    : NAV_ITEMS.find((n) => pathname.startsWith(n.path)) || NAV_ITEMS[0];
  const activeLabel = PATH_LABELS[activeItem?.path] || activeItem?.label || "Dashboard";

  const sidebarWidth = isMobile ? 0 : collapsed ? 64 : 228;

  return (
    <div style={{ background: t.bg, minHeight: "100vh", display: "flex", position: "relative" }}>
      {signOutBusy && (
        <>
          <style>{`@keyframes signout-spin { to { transform: rotate(360deg); } }`}</style>
          <div
            role="status"
            aria-live="polite"
            aria-busy="true"
            style={{
              position: "fixed",
              inset: 0,
              zIndex: 9999,
              background:
                mode === "light" ? "rgba(15,23,42,0.48)" : "rgba(0,0,0,0.72)",
              backdropFilter: "blur(2px)",
              WebkitBackdropFilter: "blur(2px)",
              display: "flex",
              flexDirection: "column",
              alignItems: "center",
              justifyContent: "center",
              gap: 16,
              fontFamily: TYPE.fontBody,
            }}
          >
            <div
              style={{
                width: 36,
                height: 36,
                border: `3px solid ${t.border}`,
                borderTopColor: t.accent,
                borderRadius: "50%",
                animation: "signout-spin 0.75s linear infinite",
              }}
            />
            <span style={{ fontSize: TYPE.sm, color: t.text, fontWeight: TYPE.medium }}>Signing out…</span>
          </div>
        </>
      )}
      {isMobile && mobileNavOpen && (
        <button
          type="button"
          aria-label="Close menu"
          onClick={() => setMobileNavOpen(false)}
          style={{
            position: "fixed",
            inset: 0,
            background: t.overlayMask,
            zIndex: 150,
            border: "none",
            cursor: "pointer",
          }}
        />
      )}
      <Sidebar
        collapsed={collapsed}
        setCollapsed={setCollapsed}
        user={user}
        isMobile={isMobile}
        mobileNavOpen={mobileNavOpen}
        onCloseMobile={() => setMobileNavOpen(false)}
      />
      <div
        style={{
          marginLeft: sidebarWidth,
          minHeight: "100vh",
          flex: 1,
          minWidth: 0,
          display: "flex",
          flexDirection: "column",
          width: isMobile ? "100%" : `calc(100vw - ${sidebarWidth}px)`,
          overflowX: "hidden",
        }}
      >
        <Topbar
          activeLabel={activeLabel}
          user={user}
          onRequestSignOut={() => setSignOutConfirmOpen(true)}
          signOutBusy={signOutBusy}
          isMobile={isMobile}
          onMenuClick={() => setMobileNavOpen(true)}
        />
        <main style={{ flex: 1, padding: isMobile ? 16 : 28, overflow: "auto", WebkitOverflowScrolling: "touch" }}>
          <Outlet context={{ addToast }} />
        </main>
      </div>
      <Toast toasts={toasts} />

      <Modal
        open={signOutConfirmOpen}
        onClose={() => !signOutBusy && setSignOutConfirmOpen(false)}
        title="Sign out?"
        width={420}
      >
        <p style={{ margin: "0 0 20px", fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.55 }}>
          You will need to sign in again to access the admin console. Continue?
        </p>
        <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, flexWrap: "wrap" }}>
          <Button variant="ghost" size="md" type="button" disabled={signOutBusy} onClick={() => setSignOutConfirmOpen(false)}>
            Cancel
          </Button>
          <Button variant="danger" size="md" type="button" disabled={signOutBusy} onClick={beginSignOutAfterConfirm}>
            Sign out
          </Button>
        </div>
      </Modal>
    </div>
  );
}
