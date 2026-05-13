import React from "react";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import TextSkeleton from "./TextSkeleton";

export default function LineOfBusinessTableRowSkeleton() {
  const { tokens: t } = useTheme();
  return (
    <tr style={{ borderBottom: `1px solid ${t.border}` }}>
      <td style={{ padding: "14px 16px", color: t.text, fontWeight: TYPE.semibold }}>
        <TextSkeleton width={180} height={12} />
      </td>
      <td style={{ padding: "14px 16px", color: t.textSecondary }}>
        <TextSkeleton width={"95%"} height={12} />
        <div style={{ height: 8 }} />
        <TextSkeleton width={"80%"} height={12} />
      </td>
      <td style={{ padding: "14px 16px" }}>
        <div
          style={{
            display: "inline-block",
            padding: "2px 10px",
            borderRadius: 6,
            border: `1px solid ${t.borderStrong}`,
            background: `${t.bgElevated}`,
          }}
        >
          <TextSkeleton width={66} height={12} />
        </div>
      </td>
      <td style={{ padding: "14px 16px", textAlign: "right" }}>
        <div style={{ display: "flex", gap: 8, justifyContent: "flex-end" }}>
          <TextSkeleton width={28} height={28} style={{ borderRadius: 8 }} />
          <TextSkeleton width={28} height={28} style={{ borderRadius: 8 }} />
          <TextSkeleton width={28} height={28} style={{ borderRadius: 8 }} />
        </div>
      </td>
    </tr>
  );
}

