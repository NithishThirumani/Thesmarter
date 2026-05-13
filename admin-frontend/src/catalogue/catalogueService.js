/**
 * Admin catalogues — /api/admin/catalogues/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/catalogues`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function listCatalogues(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", params.page);
  if (params.per_page) sp.set("per_page", params.per_page);
  if (params.company_id) sp.set("company_id", String(params.company_id));
  if (params.catalogue_status && params.catalogue_status !== "all") {
    sp.set("catalogue_status", params.catalogue_status);
  }
  if (params.search) sp.set("search", params.search);
  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load catalogues.");
  return data;
}

export async function createCatalogue(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to create catalogue.");
  return data;
}
