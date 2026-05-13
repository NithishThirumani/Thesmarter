/**
 * Admin auth context — holds user, access/refresh tokens, and auth actions.
 * Tokens stored in memory; refresh token also in sessionStorage for refresh on reload.
 */

import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import * as authService from './authService';

const AuthContext = createContext(null);

const REFRESH_KEY = 'admin_refresh_token';

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [accessToken, setAccessToken] = useState(null);
  const [refreshToken, setRefreshToken] = useState(() => {
    try {
      return typeof sessionStorage !== 'undefined' ? sessionStorage.getItem(REFRESH_KEY) : null;
    } catch (_) {
      return null;
    }
  });
  const [loading, setLoading] = useState(true);
  const [initialCheckDone, setInitialCheckDone] = useState(false);

  const clearAuth = useCallback(() => {
    setUser(null);
    setAccessToken(null);
    setRefreshToken(null);
    try { sessionStorage.removeItem(REFRESH_KEY); } catch (_) {}
    if (typeof window !== 'undefined') window.__adminAccessToken = undefined;
  }, []);

  const setTokens = useCallback((access, refresh) => {
    setAccessToken(access);
    setRefreshToken(refresh);
    if (refresh) try { sessionStorage.setItem(REFRESH_KEY, refresh); } catch (_) {}
    if (typeof window !== 'undefined') window.__adminAccessToken = access;
  }, []);

  const finishLogin = useCallback((data) => {
    const u = data.user || {};
    setUser({
      id: u.id,
      email: u.email,
      name: u.name || u.email,
      role: u.role || "super_admin",
    });
    setTokens(data.access_token, data.refresh_token);
  }, [setTokens]);

  useEffect(() => {
    if (!accessToken) return;
    let cancelled = false;
    (async () => {
      try {
        const data = await authService.me(accessToken);
        if (!cancelled && data?.user) {
          const u = data.user;
          setUser({
            id: u.id,
            email: u.email,
            name: u.name || u.email,
            role: u.role || "super_admin",
          });
        }
      } catch (_) {
        /* ignore */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [accessToken]);

  const tryRefresh = useCallback(async () => {
    const stored = sessionStorage.getItem(REFRESH_KEY);
    if (!stored) return false;
    try {
      const data = await authService.refreshToken(stored);
      if (data.access_token && data.refresh_token) {
        finishLogin(data);
        return true;
      }
    } catch (_) {
      clearAuth();
    }
    return false;
  }, [finishLogin, clearAuth]);

  useEffect(() => {
    if (initialCheckDone) return;
    let cancelled = false;
    (async () => {
      const stored = sessionStorage.getItem(REFRESH_KEY);
      if (!stored) {
        if (!cancelled) setInitialCheckDone(true);
        setLoading(false);
        return;
      }
      await tryRefresh();
      if (!cancelled) {
        setInitialCheckDone(true);
        setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [initialCheckDone, tryRefresh]);

  // Auto-refresh access token before expiry (e.g. every 14 min if access TTL is 15 min)
  useEffect(() => {
    if (!refreshToken && !sessionStorage.getItem(REFRESH_KEY)) return;
    const interval = setInterval(() => {
      tryRefresh();
    }, 14 * 60 * 1000);
    return () => clearInterval(interval);
  }, [refreshToken, tryRefresh]);

  const logout = useCallback(async () => {
    const rt = refreshToken || sessionStorage.getItem(REFRESH_KEY);
    await authService.logout(rt);
    clearAuth();
  }, [refreshToken, clearAuth]);

  const value = {
    user,
    accessToken,
    refreshToken,
    loading,
    initialCheckDone,
    setTokens,
    finishLogin,
    clearAuth,
    logout,
    tryRefresh,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
