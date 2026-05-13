import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import PageHeader from "../../components/PageHeader";
import StatCard from "../../components/StatCard";
import Card from "../../components/Card";
import Button from "../../components/Button";
import Input from "../../components/Input";
import LineChart from "../../charts/LineChart";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import * as platformDashboardService from "../../dashboard/platformDashboardService";

const KPI_ORDER_STORAGE_KEY = "platform-dashboard-kpi-order-v1";

function isoDate(d) {
  return d.toISOString().slice(0, 10);
}

function fmt(n) {
  if (n === null || n === undefined || Number.isNaN(Number(n))) return "—";
  return Number(n).toLocaleString();
}

const KPI_IDS = [
  "total_companies",
  "active_companies",
  "activation_rate",
  "new_companies_in_range",
  "products_created_range",
  "catalogues",
  "mapped_users",
  "inactive_companies",
  "attention_items",
];

function normalizeKpiOrder(stored) {
  if (!Array.isArray(stored)) return [...KPI_IDS];
  const allowed = new Set(KPI_IDS);
  const seen = new Set();
  const out = [];
  for (const id of stored) {
    if (allowed.has(id) && !seen.has(id)) {
      seen.add(id);
      out.push(id);
    }
  }
  for (const id of KPI_IDS) {
    if (!seen.has(id)) out.push(id);
  }
  return out;
}

function loadKpiOrder() {
  try {
    const raw = typeof localStorage !== "undefined" ? localStorage.getItem(KPI_ORDER_STORAGE_KEY) : null;
    if (!raw) return [...KPI_IDS];
    return normalizeKpiOrder(JSON.parse(raw));
  } catch {
    return [...KPI_IDS];
  }
}

export default function DashboardPage() {
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const today = useMemo(() => new Date(), []);
  const [dateTo, setDateTo] = useState(() => isoDate(today));
  const [dateFrom, setDateFrom] = useState(() => {
    const x = new Date(today);
    x.setUTCDate(x.getUTCDate() - 30);
    return isoDate(x);
  });
  const [companyIdFilter, setCompanyIdFilter] = useState("");
  const [busy, setBusy] = useState(false);
  const [summary, setSummary] = useState(null);
  const [growth, setGrowth] = useState(null);
  const [uploads, setUploads] = useState(null);
  const [alerts, setAlerts] = useState([]);

  const [kpiOrder, setKpiOrder] = useState(loadKpiOrder);
  const [dragKpiId, setDragKpiId] = useState(null);

  const queryParams = useMemo(() => {
    const p = {
      date_from: dateFrom,
      date_to: dateTo,
    };
    const c = companyIdFilter.trim();
    if (c !== "") p.company_id = c;
    return p;
  }, [dateFrom, dateTo, companyIdFilter]);

  const reorderKpis = useCallback((sourceId, targetId) => {
    if (!sourceId || !targetId || sourceId === targetId) return;
    setKpiOrder((prev) => {
      const order = normalizeKpiOrder(prev);
      const fromIdx = order.indexOf(sourceId);
      const toIdx = order.indexOf(targetId);
      if (fromIdx < 0 || toIdx < 0) return order;
      const next = [...order];
      next.splice(fromIdx, 1);
      next.splice(toIdx, 0, sourceId);
      const normalized = normalizeKpiOrder(next);
      try {
        localStorage.setItem(KPI_ORDER_STORAGE_KEY, JSON.stringify(normalized));
      } catch {
        /* ignore quota */
      }
      return normalized;
    });
  }, []);

  const reloadCore = useCallback(async () => {
    if (!accessToken) return;
    setBusy(true);
    try {
      const [sum, grow, up, al] = await Promise.all([
        platformDashboardService.fetchDashboardSummary(queryParams, accessToken),
        platformDashboardService.fetchDashboardGrowth(queryParams, accessToken),
        platformDashboardService.fetchDashboardUploads(queryParams, accessToken),
        platformDashboardService.fetchDashboardAlerts(queryParams, accessToken),
      ]);
      setSummary(sum?.data ?? null);
      setGrowth(grow?.data ?? null);
      setUploads(up?.data ?? null);
      setAlerts(Array.isArray(al?.data) ? al.data : []);
    } catch (e) {
      setSummary(null);
      setGrowth(null);
      setUploads(null);
      setAlerts([]);
      addToastSafe("error", "Dashboard", e.message);
    } finally {
      setBusy(false);
    }
  }, [accessToken, queryParams, addToastSafe]);

  useEffect(() => {
    void reloadCore();
  }, [reloadCore]);

  const productSpark = useMemo(() => {
    const s = growth?.products_created_by_day;
    if (!Array.isArray(s) || !s.length) return null;
    return s.slice(-8).map((d) => Number(d.count) || 0);
  }, [growth]);

  const companySpark = useMemo(() => {
    const s = growth?.companies_onboarded_by_day;
    if (!Array.isArray(s) || !s.length) return null;
    return s.slice(-8).map((d) => Number(d.count) || 0);
  }, [growth]);

  const activationRate = useMemo(() => {
    const total = Number(summary?.total_companies);
    const active = Number(summary?.active_companies);
    if (!Number.isFinite(total) || total <= 0 || !Number.isFinite(active)) return "—";
    return `${((active / total) * 100).toFixed(1)}%`;
  }, [summary]);

  const kpiDefinitions = useMemo(() => {
    const scoped = companyIdFilter.trim() ? "Filtered tenant" : "All tenants";
    return {
      total_companies: (
        <StatCard
          label="Tenant companies"
          value={fmt(summary?.total_companies)}
          sub={scoped}
          spark={companySpark}
          color={t.accent}
          icon="⬡"
        />
      ),
      active_companies: (
        <StatCard label="Active tenants" value={fmt(summary?.active_companies)} sub="Eligible active status" color={t.success} icon="✓" />
      ),
      inactive_companies: (
        <StatCard label="Inactive tenants" value={fmt(summary?.inactive_companies)} sub="Not counted as active" color={t.textMuted} icon="○" />
      ),
      activation_rate: (
        <StatCard label="Activation rate" value={activationRate} sub="Active ÷ total companies" color="#a78bfa" icon="◎" />
      ),
      new_companies_in_range: (
        <StatCard
          label="New tenants (period)"
          value={summary?.new_companies_in_range != null ? fmt(summary.new_companies_in_range) : "—"}
          sub="Companies created in date range"
          color={t.info}
          icon="＋"
        />
      ),
      products_created_range: (
        <StatCard
          label="Products created (period)"
          value={uploads?.total_events != null ? fmt(uploads.total_events) : "—"}
          sub="Catalogue products added in range"
          spark={productSpark}
          color="#38bdf8"
          icon="📦"
        />
      ),
      catalogues: (
        <StatCard
          label="Catalogues"
          value={fmt(summary?.total_catalogues)}
          sub="Across selected scope"
          color="#22d3ee"
          icon="📚"
        />
      ),
      mapped_users: (
        <StatCard
          label="Mapped users"
          value={summary?.total_mapped_users != null ? fmt(summary.total_mapped_users) : "—"}
          sub="Distinct users linked to companies"
          color="#c084fc"
          icon="👤"
        />
      ),
      attention_items: (
        <StatCard
          label="Attention items"
          value={fmt(alerts.length)}
          sub="Alerts for current filters"
          color={t.warning || "#f59e0b"}
          icon="⚑"
        />
      ),
    };
  }, [
    summary,
    uploads,
    alerts.length,
    activationRate,
    companySpark,
    productSpark,
    t,
    companyIdFilter,
  ]);

  const emptyPlatform = summary && Number(summary.total_companies) === 0 && !companyIdFilter.trim();

  const tileChrome = {
    position: "relative",
    borderRadius: 12,
    outline: "1px dashed transparent",
    transition: "outline-color 0.15s ease",
  };

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader
        title="Platform dashboard"
        subtitle="Multi-company tenant footprint, growth, and product velocity — drag KPI tiles to reorder."
        breadcrumb="Dashboard"
      />

      <div
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))",
          gap: 12,
          alignItems: "end",
          marginBottom: 20,
        }}
      >
        <Input label="Date from" type="date" value={dateFrom} onChange={(v) => setDateFrom(v)} />
        <Input label="Date to" type="date" value={dateTo} onChange={(v) => setDateTo(v)} />
        <Input
          label="Company ID (optional)"
          value={companyIdFilter}
          onChange={(v) => setCompanyIdFilter(v)}
          placeholder="All tenants"
        />
        <div style={{ display: "flex", gap: 8, alignItems: "flex-end" }}>
          <Button variant="secondary" type="button" disabled={busy} onClick={() => void reloadCore()}>
            {busy ? "Refreshing…" : "Refresh"}
          </Button>
        </div>
      </div>

      {emptyPlatform ? (
        <Card title="Getting started">
          <p style={{ margin: 0, color: t.textSecondary, fontSize: TYPE.sm }}>
            No companies onboarded yet. Create a tenant under <strong>Companies</strong> to populate this dashboard.
          </p>
        </Card>
      ) : null}

      <p style={{ margin: "0 0 10px", fontSize: TYPE.xs, color: t.textMuted }}>
        KPI tiles: drag using the handle (⠿) to rearrange. Order is saved in this browser.
      </p>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(210px, 1fr))", gap: 16, marginBottom: 24 }}>
        {normalizeKpiOrder(kpiOrder).map((id) => (
          <div
            key={id}
            draggable
            role="group"
            aria-grabbed={dragKpiId === id}
            onDragStart={(e) => {
              setDragKpiId(id);
              try {
                e.dataTransfer.setData("text/plain", id);
                e.dataTransfer.effectAllowed = "move";
              } catch {
                /* IE */
              }
            }}
            onDragEnd={() => setDragKpiId(null)}
            onDragOver={(e) => {
              e.preventDefault();
              try {
                e.dataTransfer.dropEffect = "move";
              } catch {
                /* ignore */
              }
            }}
            onDrop={(e) => {
              e.preventDefault();
              let sourceId = dragKpiId;
              try {
                sourceId = e.dataTransfer.getData("text/plain") || sourceId;
              } catch {
                /* ignore */
              }
              reorderKpis(sourceId, id);
              setDragKpiId(null);
            }}
            style={{
              ...tileChrome,
              outlineColor: dragKpiId === id ? `${t.accent}99` : "transparent",
              cursor: "grab",
            }}
          >
            <div
              style={{
                display: "flex",
                alignItems: "stretch",
                gap: 0,
                height: "100%",
              }}
            >
              <div
                title="Drag to reorder"
                aria-hidden
                style={{
                  flex: "0 0 28px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  background: t.bgElevated,
                  border: `1px solid ${t.border}`,
                  borderRight: "none",
                  borderRadius: "10px 0 0 10px",
                  color: t.textMuted,
                  fontSize: 14,
                  userSelect: "none",
                }}
              >
                ⠿
              </div>
              <div style={{ flex: 1, minWidth: 0 }}>{kpiDefinitions[id]}</div>
            </div>
          </div>
        ))}
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(320px, 1fr))", gap: 16, marginBottom: 16 }}>
        <Card title="Tenant onboarding (per day)">
          <LineChart series={growth?.companies_onboarded_by_day || []} valueKey="count" color={t.success} height={150} />
        </Card>
        <Card title="Products created (per day)">
          <LineChart series={growth?.products_created_by_day || []} valueKey="count" color={t.accent} height={150} />
        </Card>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(320px, 1fr))", gap: 16, marginBottom: 16 }}>
        <Card title="Product creation (period total)">
          <p style={{ margin: "0 0 10px", fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.5 }}>
            {uploads?.note || "Counts reflect catalogue products created in the selected date range."}
          </p>
          <div style={{ fontSize: TYPE.lg, fontWeight: TYPE.bold, color: t.text, marginBottom: 12 }}>
            {fmt(uploads?.total_events)}{" "}
            <span style={{ fontSize: TYPE.sm, color: t.textMuted, fontWeight: TYPE.normal }}>products</span>
          </div>
          <LineChart series={uploads?.by_day || []} valueKey="products_added" color="#f59e0b" height={120} />
        </Card>
        <Card title="Cross-tenant signals">
          <div style={{ maxHeight: 280, overflow: "auto" }}>
            {alerts.length === 0 ? (
              <div style={{ fontSize: TYPE.sm, color: t.textMuted }}>No alerts for current filters.</div>
            ) : (
              <table style={{ width: "100%", borderCollapse: "collapse", fontSize: TYPE.sm }}>
                <thead>
                  <tr style={{ textAlign: "left", color: t.textMuted, fontSize: TYPE.xs }}>
                    <th style={{ padding: "6px 8px" }}>Type</th>
                    <th style={{ padding: "6px 8px" }}>Company</th>
                    <th style={{ padding: "6px 8px" }}>Message</th>
                  </tr>
                </thead>
                <tbody>
                  {alerts.slice(0, 40).map((a, i) => (
                    <tr key={i} style={{ borderTop: `1px solid ${t.border}` }}>
                      <td style={{ padding: "8px", fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>{a.type}</td>
                      <td style={{ padding: "8px" }}>
                        #{a.company_id} {a.company_name ? `· ${a.company_name}` : ""}
                      </td>
                      <td style={{ padding: "8px", color: t.textSecondary }}>{a.message}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
}
