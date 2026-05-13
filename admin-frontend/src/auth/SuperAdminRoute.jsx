import React from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "./authContext";
import { ROUTES } from "../routes";
import { useTheme } from "../theme";

/**
 * Allows access only when {@link user.role} is <code>super_admin</code> (backend-enforced as well).
 */
export default function SuperAdminRoute({ children }) {
  const { user, loading, initialCheckDone } = useAuth();
  const { tokens: t } = useTheme();

  if (!initialCheckDone || loading) {
    return (
      <div
        style={{
          padding: 48,
          fontFamily: "system-ui, sans-serif",
          color: t.textMuted,
          background: t.bg,
          minHeight: "40vh",
        }}
      >
        Loading…
      </div>
    );
  }

  if (user?.role !== "super_admin") {
    return <Navigate to={ROUTES.DASHBOARD} replace />;
  }

  return children;
}
