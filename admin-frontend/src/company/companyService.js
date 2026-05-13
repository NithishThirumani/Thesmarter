/**
 * Admin company API — /admin/company/*. All requests require Bearer token.
 */

import { getApiPrefix } from '../apiPrefix.js';

const BASE = `${getApiPrefix()}/admin/company`;

function getHeaders(accessToken = null) {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
  const token = accessToken || (typeof window !== 'undefined' && window.__adminAccessToken);
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

export async function listCompanies(params = {}, accessToken) {
  const sp = new URLSearchParams();
  if (params.page) sp.set('page', params.page);
  if (params.per_page) sp.set('per_page', params.per_page);
  if (params.search) sp.set('search', params.search);
  if (params.status != null && params.status !== '' && params.status !== 'all') {
    sp.set('status', params.status);
  }
  if (params.sort) sp.set('sort', params.sort);
  if (params.company_business_id != null && String(params.company_business_id).trim() !== '') {
    sp.set('company_business_id', String(params.company_business_id).trim());
  }
  const qs = sp.toString();
  const res = await fetch(`${BASE}${qs ? `?${qs}` : ''}`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to load companies.');
  return data;
}

export async function getCompany(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Company not found.');
  return data;
}

export async function createCompany(payload, accessToken) {
  const res = await fetch(BASE, {
    method: 'POST',
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to create company.');
  return data;
}

export async function updateCompany(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update company.');
  return data;
}

export async function deleteCompany(id, accessToken) {
  const res = await fetch(`${BASE}/${id}`, {
    method: 'DELETE',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to delete company.');
  return data;
}

export async function getPaymentMethods(accessToken) {
  const res = await fetch(`${BASE}/dropdowns/payment-methods`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to load payment methods.');
  return data;
}

export async function getFeatures(accessToken) {
  const res = await fetch(`${BASE}/dropdowns/features`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to load features.');
  return data;
}

export async function getTaxTemplates(accessToken, countryCode = null, regionCode = null) {
  const sp = new URLSearchParams();
  if (countryCode && String(countryCode).trim().length === 2) {
    sp.set('country_code', String(countryCode).trim().toUpperCase());
  }
  if (regionCode != null && String(regionCode).trim() !== '') {
    sp.set('region_code', String(regionCode).trim().toUpperCase());
  }
  const qs = sp.toString();
  const res = await fetch(`${BASE}/dropdowns/tax-templates${qs ? `?${qs}` : ''}`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to load tax templates.');
  return data;
}

export async function getCountries(accessToken) {
  const res = await fetch(`${BASE}/dropdowns/countries`, {
    method: 'GET',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to load countries.');
  return data;
}

function getAuthHeadersNoContentType(accessToken) {
  const headers = { Accept: 'application/json' };
  const token = accessToken || (typeof window !== 'undefined' && window.__adminAccessToken);
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

export async function uploadCompanyLogo(id, file, accessToken) {
  const fd = new FormData();
  fd.append('logo', file);
  const res = await fetch(`${BASE}/${id}/logo`, {
    method: 'POST',
    headers: getAuthHeadersNoContentType(accessToken),
    body: fd,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Logo upload failed.');
  return data;
}

export async function deleteCompanyLogo(id, accessToken) {
  const res = await fetch(`${BASE}/${id}/logo`, {
    method: 'DELETE',
    headers: getHeaders(accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to remove logo.');
  return data;
}

export async function updateCompanyBranches(id, payload, accessToken) {
  const res = await fetch(`${BASE}/${id}/branches`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update branches.');
  return data;
}

export async function updateCompanyPaymentMethods(id, paymentMethodIds, accessToken, paymentProviders = []) {
  const res = await fetch(`${BASE}/${id}/payment-methods`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify({ payment_method_ids: paymentMethodIds, payment_providers: paymentProviders }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update payment methods.');
  return data;
}

export async function updateCompanyBusinessHours(id, businessHours, accessToken) {
  const res = await fetch(`${BASE}/${id}/business-hours`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify({ business_hours: businessHours }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update business hours.');
  return data;
}

export async function updateCompanySettings(id, settings, accessToken) {
  const res = await fetch(`${BASE}/${id}/settings`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify({ settings }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update company settings.');
  return data;
}

export async function updateCompanyTaxes(id, taxes, accessToken) {
  const res = await fetch(`${BASE}/${id}/taxes`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify({ taxes }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update taxes.');
  return data;
}

export async function updateCompanyFeatures(id, featureIds, accessToken) {
  const res = await fetch(`${BASE}/${id}/features`, {
    method: 'PUT',
    headers: getHeaders(accessToken),
    body: JSON.stringify({ feature_ids: featureIds }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Failed to update features.');
  return data;
}

export { createSuperUser } from "../users/superUserService";
