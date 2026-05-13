import React, { useMemo, useState } from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

/**
 * Shows company logo URL/data URL, or initials on a deterministic background (no extra deps).
 * Pass logoUrls (sm/md/lg from API) or a single logoUrl.
 */
export default function CompanyLogoAvatar({ name, logoUrl, logoUrls, size = 48 }) {
  const resolved = logoUrl || logoUrls?.md || logoUrls?.sm || logoUrls?.lg;
  const { tokens: t } = useTheme();
  const [imgFailed, setImgFailed] = useState(false);

  const { initials, bg } = useMemo(() => {
    const raw = String(name || "").trim();
    const parts = raw.split(/\s+/).filter(Boolean);
    let letters = "";
    if (parts.length >= 2) {
      letters = (parts[0][0] + parts[1][0]).toUpperCase();
    } else if (parts.length === 1) {
      letters = parts[0].slice(0, 2).toUpperCase();
    } else {
      letters = "?";
    }
    let hash = 0;
    for (let i = 0; i < raw.length; i++) {
      hash = raw.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = Math.abs(hash) % 360;
    const bgColor = `hsl(${hue} 42% 28%)`;
    return { initials: letters.slice(0, 2), bg: bgColor };
  }, [name]);

  if (!imgFailed && resolved && String(resolved).trim()) {
    return (
      <img
        src={resolved}
        alt=""
        width={size}
        height={size}
        onError={() => setImgFailed(true)}
        style={{
          width: size,
          height: size,
          borderRadius: Math.max(8, size * 0.2),
          objectFit: "cover",
          border: `1px solid ${t.border}`,
        }}
      />
    );
  }

  return (
    <div
      style={{
        width: size,
        height: size,
        borderRadius: Math.max(8, size * 0.2),
        background: bg,
        color: "#f4f4f5",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        fontWeight: TYPE.bold,
        fontSize: Math.max(12, size * 0.35),
        fontFamily: TYPE.fontBody,
        border: `1px solid ${t.border}`,
        flexShrink: 0,
      }}
    >
      {initials}
    </div>
  );
}
