/**
 * Admin super user APIs — /api/companies/{companyId}/super-user(s)/*
 * Bearer admin token required for all endpoints.
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/companies`;

function getHeadersJson(accessToken) {
  const h = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) h.Authorization = `Bearer ${token}`;
  return h;
}

function getHeadersMultipart(accessToken) {
  const h = { Accept: "application/json" };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) h.Authorization = `Bearer ${token}`;
  return h;
}

const H = getHeadersJson;

/**
 * GET /api/admin/super-users — all super users, optional company_id filter.
 * Rows include company_name, company_code.
 */
export async function listAllSuperUsers(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.company_id) sp.set("company_id", String(params.company_id));
  if (params.search) sp.set("search", params.search);
  if (params.status != null && params.status !== "all") sp.set("status", params.status);
  if (params.page) sp.set("page", String(params.page));
  if (params.per_page) sp.set("per_page", String(params.per_page));
  const qs = sp.toString();

  const res = await fetch(`${getApiPrefix()}/admin/super-users${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load super users.");
  return data;
}

/**
 * GET normalized mobile + registration flags before completing the form.
 */
export async function checkSuperUserMobile(companyId, mobile, accessToken) {
  const sp = new URLSearchParams({ mobile: mobile || "" });
  const res = await fetch(`${BASE}/${companyId}/super-users/mobile-check?${sp}`, {
    method: "GET",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Could not verify mobile number.");
  return data;
}

/**
 * GET list with search, status (all|active|inactive), page, per_page
 */
export async function listSuperUsers(companyId, params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.search) sp.set("search", params.search);
  if (params.status != null && params.status !== "all") sp.set("status", params.status);
  if (params.page) sp.set("page", String(params.page));
  if (params.per_page) sp.set("per_page", String(params.per_page));
  const qs = sp.toString();

  const res = await fetch(`${BASE}/${companyId}/super-users${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load super users.");
  return data;
}

export async function getSuperUser(companyId, userId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}`, {
    method: "GET",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Super user not found.");
  return data;
}

/**
 * Update with JSON object or FormData (multipart: avatar, remove_avatar).
 */
export async function updateSuperUser(companyId, userId, body, accessToken) {
  const useForm = typeof FormData !== "undefined" && body instanceof FormData;
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}`, {
    method: "PUT",
    headers: useForm ? getHeadersMultipart(accessToken) : H(accessToken),
    body: useForm ? body : JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const errs = data.errors;
    let fromFields = "";
    if (errs && typeof errs === "object") {
      fromFields = Object.values(errs)
        .flat()
        .filter(Boolean)
        .join(" ");
    }
    throw new Error(fromFields || data.message || "Update failed.");
  }
  return data;
}

export async function deleteSuperUser(companyId, userId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}`, {
    method: "DELETE",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Remove failed.");
  return data;
}

export async function listExecutiveModules(companyId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/modules`, {
    method: "GET",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load modules.");
  return data;
}

/**
 * POST create — use FormData when `options.avatarFile` is set, else JSON payload.
 * Optional `extra` merges confirm flags / permissions for conflict retries.
 */
export async function createSuperUser(companyId, payload, accessToken, options = {}) {
  const { avatarFile, extra = {} } = options;
  const bodyPayload = { ...payload, ...extra };

  const throwParsed = (res, data) => {
    const msg =
      data.errors && typeof data.errors === "object"
        ? Object.values(data.errors)
            .flat()
            .filter(Boolean)
            .join(" ")
        : data.message || "Failed to create super user.";
    const err = new Error(msg);
    err.conflict = data.conflict;
    err.details = data.data;
    err.status = res.status;
    throw err;
  };

  if (avatarFile) {
    const fd = new FormData();
    fd.append("first_name", String(bodyPayload.first_name ?? "").trim());
    fd.append("last_name", String(bodyPayload.last_name ?? "").trim());
    fd.append("mobile", String(bodyPayload.mobile ?? "").trim());
    fd.append("email", String(bodyPayload.email ?? "").trim());
    if (bodyPayload.gender) fd.append("gender", bodyPayload.gender);
    if (bodyPayload.date_of_birth) fd.append("date_of_birth", bodyPayload.date_of_birth);
    if (bodyPayload.marital_status) fd.append("marital_status", bodyPayload.marital_status);
    if (bodyPayload.confirm_convert_owner != null) fd.append("confirm_convert_owner", bodyPayload.confirm_convert_owner ? "1" : "0");
    if (bodyPayload.confirm_promote_customer != null) fd.append("confirm_promote_customer", bodyPayload.confirm_promote_customer ? "1" : "0");
    if (Array.isArray(bodyPayload.branch_ids) && bodyPayload.branch_ids.length > 0) {
      fd.append("branch_ids", JSON.stringify(bodyPayload.branch_ids.map((id) => Number(id))));
    }
    if (Array.isArray(bodyPayload.permissions) && bodyPayload.permissions.length > 0) {
      fd.append("permissions", JSON.stringify(bodyPayload.permissions));
    }
    fd.append("avatar", avatarFile);
    const res = await fetch(`${BASE}/${companyId}/super-user`, {
      method: "POST",
      headers: getHeadersMultipart(accessToken),
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throwParsed(res, data);
    return data;
  }

  const res = await fetch(`${BASE}/${companyId}/super-user`, {
    method: "POST",
    headers: H(accessToken),
    body: JSON.stringify(bodyPayload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throwParsed(res, data);
  return data;
}

export async function resendWelcomeEmail(companyId, userId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}/resend-pin`, {
    method: "POST",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Could not send email.");
  return data;
}

export async function resetSuperUserPin(companyId, userId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}/reset-pin`, {
    method: "POST",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "PIN reset failed.");
  return data;
}

export async function reactivateExecutive(companyId, userId, accessToken) {
  const res = await fetch(`${BASE}/${companyId}/super-users/${userId}/reactivate`, {
    method: "PATCH",
    headers: H(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Reactivate failed.");
  return data;
}
