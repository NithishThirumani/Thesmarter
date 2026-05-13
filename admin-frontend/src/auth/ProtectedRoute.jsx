/**
 * Protects admin routes: redirects to /login if not authenticated.
 */
import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "./authContext";
import { useTheme } from "../theme";

export default function ProtectedRoute({ children }) {
  const { user, loading, initialCheckDone } = useAuth();
  const location = useLocation();
  const { tokens: t } = useTheme();

  if (!initialCheckDone || loading) {
    return (
      <div
        style={{
          minHeight: "100vh",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: t.bg,
          color: t.textMuted,
          fontFamily: "system-ui, sans-serif",
        }}
      >
        Loading…
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return children;
}
