/**
 * Admin country tax templates — /api/admin/tax-template/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/tax-template`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function listTaxTemplates(accessToken, params = {}) {
  const sp = new URLSearchParams();
  if (params.country_code) sp.set("country_code", String(params.country_code).trim().toUpperCase());
  if (params.region_code) sp.set("region_code", String(params.region_code).trim().toUpperCase());
  if (params.per_page) sp.set("per_page", params.per_page);
  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load tax templates.");
  return data;
}

export async function getTaxTemplate(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load tax template.");
  return data;
}

export async function createTaxTemplate(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to create tax template.");
  return data;
}

export async function updateTaxTemplate(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to update tax template.");
  return data;
}

export async function deactivateTaxTemplate(id, accessToken) {
  const res = await fetch(`${BASE}/${id}/deactivate`, {
    method: "POST",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to deactivate tax template.");
  return data;
}

export async function deleteTaxTemplate(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "DELETE",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to delete tax template.");
  return data;
}
