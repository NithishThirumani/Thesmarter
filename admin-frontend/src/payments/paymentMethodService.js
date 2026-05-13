/**
 * Admin Payment Methods API — /api/admin/payment-methods/*
 */

import { getApiPrefix } from "../apiPrefix.js";

const BASE = `${getApiPrefix()}/admin/payment-methods`;

function getHeaders(accessToken = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  const token = accessToken || (typeof window !== "undefined" && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function listPaymentMethods(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", params.page);
  if (params.per_page) sp.set("per_page", params.per_page);
  if (params.search) sp.set("search", params.search);
  if (params.payment_status && params.payment_status !== "all") sp.set("payment_status", params.payment_status);

  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ""}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load payment methods.");
  return data;
}

export async function getPaymentMethod(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "GET",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to load payment method.");
  return data;
}

export async function createPaymentMethod(payload, accessToken) {
  const res = await fetch(BASE, {
    method: "POST",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to create payment method.");
  return data;
}

export async function updatePaymentMethod(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "PUT",
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to update payment method.");
  return data;
}

export async function deletePaymentMethod(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: "DELETE",
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || "Failed to delete payment method.");
  return data;
}
