import React from "react";
import { useTheme } from "../../theme";

export default function AvatarSkeleton({ size = 32 }) {
  const { tokens: t } = useTheme();
  const highlight = `${t.accent}33`; // brighter highlight on dark theme
  return (
    <div
      style={{
        width: size,
        height: size,
        borderRadius: "50%",
        border: `1px solid ${t.borderStrong}`,
        background: `linear-gradient(90deg, ${t.bgElevated} 0%, ${highlight} 50%, ${t.bgElevated} 100%)`,
        backgroundSize: "240% 100%",
        animation: "theSmartr-shimmer 1.1s ease-in-out infinite",
        flexShrink: 0,
        opacity: 0.95,
        overflow: "hidden",
        willChange: "background-position",
      }}
    />
  );
}

