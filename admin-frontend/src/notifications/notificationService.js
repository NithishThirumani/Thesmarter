import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/notifications`;

function getHeaders(accessToken) {
  const headers = {
    Accept: "application/json",
  };
  if (accessToken) headers.Authorization = `Bearer ${accessToken}`;
  return headers;
}

/**
 * @param {{ page?: number, per_page?: number }} params
 * @param {string|null} accessToken
 */
export async function listNotifications(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", String(params.page));
  if (params.per_page) sp.set("per_page", String(params.per_page));
  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load notifications.");
  return data;
}
