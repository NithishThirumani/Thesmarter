/**
 * Laravel `/api` prefix as seen by the browser.
 *
 * - Empty `VITE_API_BASE_URL`: `/api` (same origin → Vite dev proxy).
 * - `https://host`: `https://host/api`
 * - `https://host/v1/notification`: `https://host/v1/notification/api` (gateway prefix before Laravel routes).
 */
export function getApiPrefix() {
  const raw = import.meta.env.VITE_API_BASE_URL;
  if (raw === undefined || raw === null || String(raw).trim() === '') {
    return '/api';
  }
  const base = String(raw).trim().replace(/\/+$/, '');
  return `${base}/api`;
}
