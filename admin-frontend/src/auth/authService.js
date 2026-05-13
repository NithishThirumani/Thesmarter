/**
 * Admin auth service — calls /admin/auth/* only. Isolated from existing API integrations.
 */

import { getApiPrefix } from '../apiPrefix.js';

const BASE = `${getApiPrefix()}/admin/auth`;

function getHeaders(includeAuth = false, accessToken = null) {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
  const token = accessToken || (typeof window !== 'undefined' && window.__adminAccessToken);
  if (includeAuth && token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

export async function login(email) {
  const res = await fetch(`${BASE}/login`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({ email: email.trim().toLowerCase() }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Login request failed.');
  }
  return data;
}

export async function verifyPin(email, pin) {
  const res = await fetch(`${BASE}/verify-pin`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({ email: email.trim().toLowerCase(), pin: String(pin) }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Invalid PIN.');
  }
  return data;
}

export async function verifyOtp(email, otp) {
  const res = await fetch(`${BASE}/verify-otp`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({ email: email.trim().toLowerCase(), otp: String(otp) }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Invalid OTP.');
  }
  return data;
}

export async function refreshToken(refreshToken) {
  const res = await fetch(`${BASE}/refresh-token`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({ refresh_token: refreshToken }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Refresh failed.');
  }
  return data;
}

export async function logout(refreshToken) {
  let res;
  try {
    res = await fetch(`${BASE}/logout`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ refresh_token: refreshToken || '' }),
    });
  } catch (e) {
    throw new Error(e?.message || 'Network error while signing out.');
  }
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Could not sign out. Please try again.');
  }
}

export async function me(accessToken) {
  const res = await fetch(`${BASE}/me`, {
    method: 'GET',
    headers: getHeaders(true, accessToken),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || 'Unauthorized.');
  }
  return data;
}

export async function forgotPin(email) {
  const res = await fetch(`${BASE}/forgot-pin`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({ email: email.trim().toLowerCase() }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Request failed.');
  return data;
}

export async function resetPin(email, otp, newPin) {
  const res = await fetch(`${BASE}/reset-pin`, {
    method: 'POST',
    headers: getHeaders(),
    body: JSON.stringify({
      email: email.trim().toLowerCase(),
      otp: String(otp),
      new_pin: String(newPin),
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Reset failed.');
  return data;
}

export async function changePin(currentPin, newPin, accessToken) {
  const res = await fetch(`${BASE}/change-pin`, {
    method: 'POST',
    headers: getHeaders(true, accessToken),
    body: JSON.stringify({
      current_pin: String(currentPin),
      new_pin: String(newPin),
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Change PIN failed.');
  return data;
}
