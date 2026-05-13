/**
 * Platform admin accounts API — /admin/platform-admins/* (super-admin JWT only).
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/platform-admins`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

export async function listPlatformAdmins(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", params.page);
  if (params.per_page) sp.set("per_page", params.per_page);
  if (params.search) sp.set("search", params.search);
  if (params.status && params.status !== "all") sp.set("status", params.status);
  if (params.sort) sp.set("sort", params.sort);
  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load platform admins.");
  return data;
}

export async function createPlatformAdmin(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(" ") : data.message;
    throw new Error(msg || "Create failed.");
  }
  return data;
}

export async function updatePlatformAdmin(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${encodeURIComponent(id)}`, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(" ") : data.message;
    throw new Error(msg || "Update failed.");
  }
  return data;
}

export async function patchPlatformAdminStatus(id, isActive, accessToken) {
  const res = await fetch(`${BASE}/${encodeURIComponent(id)}/status`, {
    method: "PATCH",
    headers: getHeaders(accessToken),
    body: JSON.stringify({ is_active: !!isActive }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Status update failed.");
  return data;
}

export async function resetPlatformAdminPin(id, accessToken) {
  const res = await fetch(`${BASE}/${encodeURIComponent(id)}/reset-pin`, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify({}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Reset PIN failed.");
  return data;
}

export async function deletePlatformAdmin(id, accessToken) {
  const res = await fetch(`${BASE}/${encodeURIComponent(id)}`, {
    method: "DELETE",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(" ") : data.message;
    throw new Error(msg || "Delete failed.");
  }
  return data;
}
