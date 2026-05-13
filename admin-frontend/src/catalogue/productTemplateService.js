/**
 * Admin bulk product upload template — /api/admin/products/*
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

export async function getTemplateMeta(companyId, accessToken) {
  const sp = new URLSearchParams({ company_id: String(companyId) });
  const res = await fetch(`${BASE}/template-meta?${sp.toString()}`, {
    method: "GET",
    headers: bearerHeaders(accessToken, { Accept: "application/json" }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || "Failed to load template prerequisites.");
  }
  return data;
}

function parseFilenameFromContentDisposition(header) {
  if (!header || typeof header !== "string") return null;
  const star = /filename\*=UTF-8''([^;\n]+)/i.exec(header);
  if (star) {
    try {
      return decodeURIComponent(star[1].trim());
    } catch {
      return star[1].trim();
    }
  }
  const quoted = /filename="([^"]+)"/i.exec(header);
  if (quoted) return quoted[1].trim();
  const plain = /filename=([^;\n]+)/i.exec(header);
  if (plain) return plain[1].trim().replace(/^["']|["']$/g, "");
  return null;
}

/**
 * @param {string} [fallbackFilename] — used if Content-Disposition is missing (e.g. client-side slug from company name)
 * @returns {Promise<{ blob: Blob, filename: string }>}
 */
export async function downloadProductBulkTemplate(companyId, catalogueId, accessToken, fallbackFilename) {
  const sp = new URLSearchParams({
    company_id: String(companyId),
    catalogue_id: String(catalogueId),
  });
  const res = await fetch(`${BASE}/template?${sp.toString()}`, {
    method: "GET",
    headers: bearerHeaders(accessToken),
  });
  const ct = (res.headers.get("Content-Type") || "").toLowerCase();
  if (!res.ok) {
    if (ct.includes("application/json")) {
      const j = await res.json().catch(() => ({}));
      throw new Error(j.message || "Template download failed.");
    }
    const text = await res.text().catch(() => "");
    throw new Error(text || "Template download failed.");
  }
  const blob = await res.blob();
  const headerName = parseFilenameFromContentDisposition(res.headers.get("Content-Disposition"));
  const filename =
    headerName ||
    fallbackFilename ||
    `company-${companyId}-catalogue-${catalogueId}-product-upload-template.xlsx`;
  return { blob, filename };
}
