import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function PageHeader({ title, subtitle, action, breadcrumb }) {
    const { tokens: t } = useTheme();
    return (
        <div style={{ marginBottom: 28, fontFamily: TYPE.fontBody }}>
            {breadcrumb && (
                <div style={{
                    fontSize: TYPE.xs, color: t.textMuted, marginBottom: 10,
                    display: "flex", gap: 6, alignItems: "center", letterSpacing: "0.02em"
                }}>
                    <span style={{ color: t.textMuted }}>Admin</span>
                    <span style={{ color: t.border, fontSize: 10 }}>›</span>
                    <span style={{ color: t.accent, fontWeight: TYPE.medium }}>{breadcrumb}</span>
                </div>
            )}
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", flexWrap: "wrap", gap: 12 }}>
                <div>
                    <h1 style={{
                        margin: 0, fontSize: TYPE["2xl"], fontWeight: TYPE.black, color: t.text,
                        letterSpacing: "-0.03em", fontFamily: TYPE.fontDisplay, lineHeight: 1.2
                    }}>{title}</h1>
                    {subtitle && (
                        <p style={{
                            margin: "5px 0 0", fontSize: TYPE.base, color: t.textSecondary,
                            fontFamily: TYPE.fontBody, lineHeight: 1.5
                        }}>{subtitle}</p>
                    )}
                </div>
                {action}
            </div>
        </div>
    );
};
