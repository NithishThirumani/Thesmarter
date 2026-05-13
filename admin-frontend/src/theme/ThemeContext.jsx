import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { THEME } from "./theme.jsx";

/* eslint-disable react-refresh/only-export-components -- ThemeProvider + useTheme are intentionally co-located */

const STORAGE_KEY = "thesmartr-admin-theme";

const ThemeContext = createContext(null);

export function ThemeProvider({ children }) {
  const [mode, setMode] = useState(() => {
    if (typeof window === "undefined") return "dark";
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored === "light" || stored === "dark") return stored;
    } catch {
      /* ignore */
    }
    try {
      return window.matchMedia("(prefers-color-scheme: light)").matches ? "light" : "dark";
    } catch {
      return "dark";
    }
  });

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, mode);
    } catch {
      /* ignore */
    }
    const root = document.documentElement;
    root.setAttribute("data-theme", mode);
    const track = mode === "dark" ? "#0a0a0a" : "#f4f4f5";
    const thumb = mode === "dark" ? "#2a2a2a" : "#cbd5e1";
    root.style.setProperty("--app-scrollbar-track", track);
    root.style.setProperty("--app-scrollbar-thumb", thumb);
    root.style.setProperty("--app-placeholder", mode === "dark" ? "#737373" : "#71717a");
  }, [mode]);

  const toggleMode = useCallback(() => {
    setMode((m) => (m === "dark" ? "light" : "dark"));
  }, []);

  const tokens = useMemo(() => THEME[mode], [mode]);

  const value = useMemo(
    () => ({ mode, setMode, toggleMode, tokens }),
    [mode, toggleMode, tokens]
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error("useTheme must be used within ThemeProvider");
  }
  return ctx;
}
