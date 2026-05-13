/**
 * Admin Line of Business API — /api/admin/line-of-business/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/line-of-business`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function listLineOfBusiness(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", params.page);
  if (params.per_page) sp.set("per_page", params.per_page);
  if (params.search) sp.set("search", params.search);
  if (params.lob_status && params.lob_status !== "all") sp.set("lob_status", params.lob_status);

  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load line of business.");
  return data;
}

export async function getLineOfBusiness(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load line of business.");
  return data;
}

export async function createLineOfBusiness(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to create line of business.");
  return data;
}

export async function updateLineOfBusiness(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to update line of business.");
  return data;
}

export async function deleteLineOfBusiness(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "DELETE",
    headers: getHeaders(accessToken),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to delete line of business.");
  return data;
}

export async function getLineOfBusinessDropdowns(accessToken) {
  const res = await fetch(`${BASE}/dropdowns`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load line of business options.");
  return data;
}

