/**
 * Merchant bulk product import — multipart /admin/products/bulk-upload
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/products`;

function bearerHeaders(accessToken, extra = {}) {
  const headers = { ...extra };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

/**
 * @param {FormData} formData — expects: file, company_id, catalogue_id, dry_run?, acting_user_id?
 */
export async function bulkUploadProducts(formData, accessToken) {
  const res = await fetch(`${BASE}/bulk-upload`, {
    method: "POST",
    headers: bearerHeaders(accessToken, { Accept: "application/json" }),
    body: formData,
  });
  const data = await res.json().catch(() => ({}));

  const payload = data.data !== undefined ? data.data : data;
  const okSuccess = data.success !== false;

  return { ok: res.ok && okSuccess, status: res.status, data: payload, raw: data };
}

/** GET bulk-upload result after async import (polling). */
export async function bulkUploadPollResult(token, accessToken) {
  const sp = new URLSearchParams({ token: String(token) });
  const res = await fetch(`${BASE}/bulk-upload/result?${sp.toString()}`, {
    method: "GET",
    headers: bearerHeaders(accessToken, { Accept: "application/json" }),
  });
  const data = await res.json().catch(() => ({}));
  const payload = data.data !== undefined ? data.data : data;

  return { ok: res.ok, status: res.status, data: payload, raw: data };
}

/** Trigger browser download from UTF-8 CSV text. */
export function downloadErrorReportCsv(csvText, filenameBase = "bulk-product-upload-errors") {
  const blob = new Blob([csvText || ""], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${filenameBase}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}
