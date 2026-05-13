import React from "react";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import { MOCK_AUDIT } from "../../theSmartr";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";

export default function AuditPage() {
  const { tokens: t } = useTheme();

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader title="Audit" subtitle="Security and activity log" breadcrumb="Audit" />
      <Card title="Recent activity">
        {MOCK_AUDIT.map((a, idx) => (
          <div
            key={a.id}
            style={{
              display: "flex",
              gap: 14,
              padding: "12px 0",
              borderBottom: idx < MOCK_AUDIT.length - 1 ? `1px solid ${t.border}` : "none",
              alignItems: "flex-start",
            }}
          >
            <div style={{ width: 7, height: 7, borderRadius: "50%", background: t.accent, marginTop: 6, flexShrink: 0 }} />
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ fontSize: TYPE.sm, color: t.text, fontWeight: TYPE.semibold, lineHeight: 1.4 }}>{a.action}</div>
              <div style={{ fontSize: TYPE.xs, color: t.textMuted, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", marginTop: 2 }}>{a.details}</div>
            </div>
            <div style={{ fontSize: TYPE.xs, color: t.textMuted, flexShrink: 0, fontVariantNumeric: "tabular-nums" }}>{a.time.split(" ")[1]}</div>
          </div>
        ))}
      </Card>
    </div>
  );
}
