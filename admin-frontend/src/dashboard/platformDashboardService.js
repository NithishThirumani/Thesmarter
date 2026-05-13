/**
 * Platform admin dashboard — GET /api/admin/dashboard/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/dashboard`;

function bearerHeaders(accessToken, extra = {}) {
  const headers = { Accept: "application/json", ...extra };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

function buildQuery(params = {}) {
  const sp = new URLSearchParams();
  if (params.date_from) sp.set("date_from", params.date_from);
  if (params.date_to) sp.set("date_to", params.date_to);
  if (params.company_id != null && String(params.company_id).trim() !== "") {
    sp.set("company_id", String(params.company_id).trim());
  }
  return sp.toString();
}

export async function fetchDashboardSummary(params, accessToken) {
  const qs = buildQuery(params);
  const res = await fetch(`${BASE}/summary${qs ? `?${qs}` : ""}`, { headers: bearerHeaders(accessToken) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load dashboard summary.");
  return data;
}

export async function fetchDashboardCompanies(params, accessToken) {
  const sp = new URLSearchParams(buildQuery(params));
  if (params.page) sp.set("page", String(params.page));
  if (params.per_page) sp.set("per_page", String(params.per_page));
  if (params.search) sp.set("search", params.search);
  if (params.company_status != null && params.company_status !== "" && params.company_status !== "all") {
    sp.set("company_status", String(params.company_status));
  }
  const qs = sp.toString();
  const res = await fetch(`${BASE}/companies${qs ? `?${qs}` : ""}`, { headers: bearerHeaders(accessToken) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load company insights.");
  return data;
}

export async function fetchDashboardGrowth(params, accessToken) {
  const qs = buildQuery(params);
  const res = await fetch(`${BASE}/growth${qs ? `?${qs}` : ""}`, { headers: bearerHeaders(accessToken) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load growth metrics.");
  return data;
}

export async function fetchDashboardUploads(params, accessToken) {
  const qs = buildQuery(params);
  const res = await fetch(`${BASE}/uploads${qs ? `?${qs}` : ""}`, { headers: bearerHeaders(accessToken) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load upload insights.");
  return data;
}

export async function fetchDashboardAlerts(params, accessToken) {
  const qs = buildQuery(params);
  const res = await fetch(`${BASE}/alerts${qs ? `?${qs}` : ""}`, { headers: bearerHeaders(accessToken) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load alerts.");
  return data;
}
