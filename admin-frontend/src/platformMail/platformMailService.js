/**
 * Platform mail settings API — overrides Laravel mail config from DB (/api/admin/platform/mail-settings)
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/platform/mail-settings`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function getMailSettings(accessToken) {
  const res = await fetch(BASE, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Could not load mail settings.");
  return data;
}

export async function saveMailSettings(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Could not save mail settings.");
  return data;
}

export async function sendTestMail(testEmail, accessToken, extraPayload = {}) {
  const res = await fetch(BASE + "/test", {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify({ test_email: testEmail, ...extraPayload }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Test mail failed.");
  return data;
}
