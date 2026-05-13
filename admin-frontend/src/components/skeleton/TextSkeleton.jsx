import React from "react";
import { useTheme } from "../../theme";

export default function TextSkeleton({ width = "100%", height = 12, style = {} }) {
  const { tokens: t } = useTheme();
  const highlight = `${t.accent}33`; // brighter highlight on dark theme
  return (
    <div
      style={{
        width,
        height,
        borderRadius: 8,
        border: `1px solid ${t.border}`,
        background: `linear-gradient(90deg, ${t.bgElevated} 0%, ${highlight} 50%, ${t.bgElevated} 100%)`,
        backgroundSize: "240% 100%",
        animation: "theSmartr-shimmer 1.1s ease-in-out infinite",
        opacity: 0.95,
        overflow: "hidden",
        willChange: "background-position",
        ...style,
      }}
    >
      <style>{`
        @keyframes theSmartr-shimmer {
          0% { background-position: 0% 0; }
          100% { background-position: -240% 0; }
        }
      `}</style>
    </div>
  );
}

