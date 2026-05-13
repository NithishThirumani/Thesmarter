import React, { useCallback, useEffect, useState } from "react";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import { useAuth } from "../../auth/authContext";
import * as notificationService from "../../notifications/notificationService";

function formatListTime(iso) {
  if (!iso) return "—";
  try {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(iso);
    return new Intl.DateTimeFormat(undefined, {
      dateStyle: "medium",
      timeStyle: "short",
    }).format(d);
  } catch (_) {
    return String(iso);
  }
}

const kindStyle = (kind, t) => {
  switch (kind) {
    case "success":
      return { bg: "rgba(22, 163, 74, 0.12)", border: "rgba(22, 163, 74, 0.35)", dot: "#16a34a" };
    case "warning":
      return { bg: "rgba(217, 119, 6, 0.12)", border: "rgba(217, 119, 6, 0.35)", dot: "#d97706" };
    case "error":
      return { bg: "rgba(220, 38, 38, 0.1)", border: "rgba(220, 38, 38, 0.35)", dot: "#dc2626" };
    default:
      return { bg: t.bgElevated || "rgba(15, 35, 63, 0.04)", border: t.border, dot: t.accent || "#2563eb" };
  }
};

export default function NotificationsPage() {
  const { tokens: t } = useTheme();
  const { accessToken } = useAuth();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState([]);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    if (!accessToken) {
      setItems([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const res = await notificationService.listNotifications({ per_page: 50, page: 1 }, accessToken);
      setItems(Array.isArray(res.data) ? res.data : []);
    } catch (e) {
      setError(e.message || "Could not load notifications.");
      setItems([]);
    } finally {
      setLoading(false);
    }
  }, [accessToken]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <div>
      <PageHeader title="Notifications" subtitle="Catalogue and import activity from the platform" breadcrumb="Notifications" />
      <Card>
        <div style={{ padding: "18px 20px 8px", borderBottom: `1px solid ${t.border}` }}>
          <div style={{ fontFamily: TYPE.fontBody, fontSize: TYPE.sm, color: t.textMuted, lineHeight: 1.5 }}>
            New catalogue rows and completed product bulk imports are logged here automatically. Run{" "}
            <code style={{ fontSize: 12 }}>php artisan migrate</code> on the API if this list stays empty after those actions.
          </div>
        </div>
        {error ? (
          <div style={{ padding: 20, color: "#b91c1c", fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>{error}</div>
        ) : loading ? (
          <div style={{ padding: 24, textAlign: "center", color: t.textMuted, fontFamily: TYPE.fontBody }}>Loading…</div>
        ) : items.length === 0 ? (
          <div style={{ padding: 24, textAlign: "center", color: t.textMuted, fontFamily: TYPE.fontBody }}>
            No notifications yet. Create a catalogue or finish a bulk product import to see entries here.
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: 0 }}>
            {items.map((n) => {
              const ks = kindStyle(n.kind, t);
              return (
                <div
                  key={n.id}
                  style={{
                    display: "grid",
                    gridTemplateColumns: "12px 1fr",
                    gap: 14,
                    padding: "16px 20px",
                    borderBottom: `1px solid ${t.border}`,
                    alignItems: "start",
                    fontFamily: TYPE.fontBody,
                  }}
                >
                  <span
                    aria-hidden
                    style={{
                      width: 10,
                      height: 10,
                      borderRadius: 999,
                      background: ks.dot,
                      marginTop: 6,
                    }}
                  />
                  <div>
                    <div style={{ display: "flex", justifyContent: "space-between", gap: 12, flexWrap: "wrap", marginBottom: 6 }}>
                      <div style={{ fontWeight: TYPE.semibold, color: t.text, fontSize: TYPE.base }}>{n.title}</div>
                      <div style={{ fontSize: TYPE.xs, color: t.textMuted, whiteSpace: "nowrap" }}>{formatListTime(n.created_at)}</div>
                    </div>
                    <div
                      style={{
                        fontSize: TYPE.sm,
                        color: t.textSecondary,
                        lineHeight: 1.5,
                        padding: "10px 12px",
                        borderRadius: 10,
                        background: ks.bg,
                        border: `1px solid ${ks.border}`,
                      }}
                    >
                      {n.body}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </Card>
    </div>
  );
}
