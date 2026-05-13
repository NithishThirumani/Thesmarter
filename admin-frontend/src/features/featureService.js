/**
 * Admin Features API — /api/admin/features/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/features`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function listFeatures(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", params.page);
  if (params.per_page) sp.set("per_page", params.per_page);
  if (params.search) sp.set("search", params.search);
  if (params.feature_status && params.feature_status !== "all") sp.set("feature_status", params.feature_status);

  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load features.");
  return data;
}

export async function createFeature(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to create feature.");
  return data;
}

export async function updateFeature(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to update feature.");
  return data;
}

export async function deleteFeature(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "DELETE",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to delete feature.");
  return data;
}

export async function listFeatureDropdowns(accessToken) {
  const res = await fetch(`${BASE}/dropdowns`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load feature options.");
  return data;
}

