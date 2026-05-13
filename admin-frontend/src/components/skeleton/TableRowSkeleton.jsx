import React from "react";
import { useTheme } from "../../theme";
import AvatarSkeleton from "./AvatarSkeleton";
import TextSkeleton from "./TextSkeleton";

export default function TableRowSkeleton() {
  const { tokens: t } = useTheme();
  return (
    <tr style={{ borderBottom: `1px solid ${t.border}` }}>
      {/* Ensure keyframes exist even if row doesn't contain TextSkeleton (defensive) */}
      <style>{`
        @keyframes theSmartr-shimmer {
          0% { background-position: 0% 0; }
          100% { background-position: -240% 0; }
        }
      `}</style>
      <td style={{ padding: "12px 14px", width: 44 }}>
        <div
          style={{
            width: 14,
            height: 14,
            borderRadius: 4,
            background: `linear-gradient(90deg, ${t.bgElevated} 0%, ${t.accent}33 50%, ${t.bgElevated} 100%)`,
            backgroundSize: "240% 100%",
            animation: "theSmartr-shimmer 1.1s ease-in-out infinite",
            border: `1px solid ${t.borderStrong}`,
            opacity: 0.95,
            overflow: "hidden",
            willChange: "background-position",
          }}
        />
      </td>
      <td style={{ padding: "12px 14px", width: 54 }}>
        <AvatarSkeleton size={32} />
      </td>
      <td style={{ padding: "12px 14px" }}>
        <TextSkeleton width={84} height={12} />
      </td>
      <td style={{ padding: "12px 14px", minWidth: 220 }}>
        <TextSkeleton width={"80%"} height={12} />
      </td>
      <td style={{ padding: "12px 14px", width: 140 }}>
        <TextSkeleton width={90} height={12} />
      </td>
      <td style={{ padding: "12px 14px", textAlign: "right", width: 220 }}>
        <div style={{ display: "flex", gap: 8, justifyContent: "flex-end" }}>
          <TextSkeleton width={56} height={12} />
          <TextSkeleton width={56} height={12} />
          <TextSkeleton width={66} height={12} />
        </div>
      </td>
    </tr>
  );
}

