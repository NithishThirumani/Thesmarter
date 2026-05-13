import React, { useMemo } from "react";

/**
 * Lightweight responsive line chart (no external deps). `series` items: { [valueKey]: number, [labelKey]: string }.
 */
export default function LineChart({
  series = [],
  valueKey = "count",
  color = "#E8C547",
  height = 140,
  emptyLabel = "No data in this range",
}) {
  const { points, max, padX, padY, w } = useMemo(() => {
    const data = Array.isArray(series) ? series : [];
    const innerW = Math.max(240, Math.min(800, typeof window !== "undefined" ? window.innerWidth - 120 : 600));
    if (data.length === 0) {
      return { points: [], max: 0, padX: 8, padY: 14, w: innerW };
    }
    const vals = data.map((d) => Number(d[valueKey]) || 0);
    const m = Math.max(...vals, 1);
    const px = 8;
    const py = 14;
    const innerH = Math.max(height - py * 2, 40);
    const step = data.length <= 1 ? 0 : (innerW - px * 2) / (data.length - 1);
    const pts = data.map((d, i) => {
      const v = Number(d[valueKey]) || 0;
      const x = px + step * i;
      const y = py + innerH - (v / m) * innerH;
      return `${x},${y}`;
    });
    return { points: pts, max: m, padX: px, padY: py, w: innerW };
  }, [series, valueKey, height]);

  if (!series?.length) {
    return (
      <div
        style={{
          height,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          opacity: 0.45,
          fontSize: 13,
        }}
      >
        {emptyLabel}
      </div>
    );
  }

  return (
    <svg width="100%" height={height} viewBox={`0 0 ${w} ${height}`} preserveAspectRatio="none" style={{ display: "block" }}>
      <polyline fill="none" stroke={color} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" points={points.join(" ")} />
      {series.map((d, i) => {
        const step = series.length <= 1 ? 0 : (w - padX * 2) / (series.length - 1);
        const v = Number(d[valueKey]) || 0;
        const x = padX + step * i;
        const innerH = Math.max(height - padY * 2, 40);
        const y = padY + innerH - (v / max) * innerH;
        return <circle key={i} cx={x} cy={y} r="3.5" fill={color} opacity={0.95} />;
      })}
    </svg>
  );
}
