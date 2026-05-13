import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";

export default function Loader() {
    const { tokens: t } = useTheme();
    return (
        <div style={{ display: "flex", alignItems: "center", justifyContent: "center", height: 200, gap: 12 }}>
            <div style={{
                width: 28, height: 28, border: `2px solid ${t.bgSubtle}`,
                borderTop: `2px solid ${t.accent}`,
                borderRadius: "50%", animation: "spin 0.75s linear infinite"
            }} />
            <span style={{ fontSize: TYPE.sm, color: t.textMuted, fontFamily: TYPE.fontBody }}>Loading…</span>
        </div>
    );
}