import React from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
import SparkLine from "../charts/SparkLine";

export default function StatCard({ label, value, sub, spark, color = "#E8C547", icon }) {
    const { tokens: t } = useTheme();
    return (
        <div style={{
            background: t.bgCard, border: `1px solid ${t.border}`, borderRadius: 10,
            padding: "22px 24px", display: "flex", flexDirection: "column", gap: 10,
            position: "relative", overflow: "hidden", fontFamily: TYPE.fontBody,
        }}>
            <div style={{ position: "absolute", top: 0, left: 0, right: 0, height: 2, background: color }} />
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start" }}>
                <span style={{
                    fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textMuted,
                    letterSpacing: "0.08em", textTransform: "uppercase"
                }}>{label}</span>
                <span style={{ fontSize: 20, opacity: 0.7 }}>{icon}</span>
            </div>
            <div style={{
                fontSize: TYPE["3xl"], fontWeight: TYPE.black, color: t.text,
                letterSpacing: "-0.04em", lineHeight: 1, fontFamily: TYPE.fontDisplay
            }}>{value}</div>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end" }}>
                <span style={{ fontSize: TYPE.sm, color: t.textMuted, lineHeight: 1.4 }}>{sub}</span>
                {spark && <SparkLine data={spark} color={color} />}
            </div>
        </div>
    );
};
