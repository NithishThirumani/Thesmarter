/**
 * Enterprise theme: black / white + yellow accent. Use via useTheme().tokens (dark | light).
 */
export const THEME = {
  dark: {
    bg: "#0a0a0a",
    bgCard: "#141414",
    bgHover: "#1a1a1a",
    bgElevated: "#111111",
    bgSubtle: "#1f1f1f",

    border: "#2a2a2a",
    borderStrong: "#333333",

    text: "#ffffff",
    textSecondary: "#a3a3a3",
    textMuted: "#737373",

    accent: "#FACC15",
    accentHover: "#FDE047",
    accentDim: "#CA8A04",
    accentSubtle: "#422006",

    success: "#22c55e",
    successBg: "#22c55e20",
    warning: "#eab308",
    danger: "#ef4444",
    dangerBg: "#ef444420",
    info: "#3b82f6",

    sidebarBg: "#0a0a0a",
    topbarBg: "#141414",

    overlayMask: "rgba(0,0,0,0.55)",
    shadowElevated: "0 32px 80px rgba(0,0,0,0.55)",
    dotGrid: "rgba(250,204,21,0.08)",
    loginDotGrid: "rgba(250,204,21,0.08)",

    /** Login canvas (distinct from card) */
    loginBackdrop: "#080808",
    loginOrbGlow: "rgba(250,204,21,0.06)",
    loginCardBorder: "#404040",
    loginCardShadow: "0 32px 80px rgba(0,0,0,0.55), 0 0 0 1px rgba(250,204,21,0.06)",
  },

  light: {
    bg: "#fafafa",
    bgCard: "#ffffff",
    bgHover: "#f4f4f5",
    bgElevated: "#f4f4f5",
    bgSubtle: "#e4e4e7",

    border: "#e4e4e7",
    borderStrong: "#d4d4d8",

    text: "#18181b",
    textSecondary: "#52525b",
    textMuted: "#71717a",

    accent: "#CA8A04",
    accentHover: "#EAB308",
    accentDim: "#A16207",
    accentSubtle: "#fef9c3",

    success: "#16a34a",
    successBg: "#16a34a18",
    warning: "#ca8a04",
    danger: "#dc2626",
    dangerBg: "#dc262618",
    info: "#2563eb",

    sidebarBg: "#ffffff",
    topbarBg: "#ffffff",

    overlayMask: "rgba(0,0,0,0.35)",
    shadowElevated: "0 24px 60px rgba(0,0,0,0.08)",
    dotGrid: "rgba(202,138,4,0.12)",
    loginDotGrid: "rgba(71,85,105,0.22)",

    /** Login: tinted canvas so white card reads clearly */
    loginBackdrop: "#dde3ea",
    loginOrbGlow: "rgba(202,138,4,0.12)",
    loginCardBorder: "#cbd5e1",
    loginCardShadow:
      "0 1px 2px rgba(15,23,42,0.06), 0 8px 24px rgba(15,23,42,0.08), 0 24px 56px rgba(15,23,42,0.1), 0 0 0 1px rgba(148,163,184,0.35)",
  },
};
