import React from "react";

export default function SparkLine({ data = [], color }) {
  const width = 120;
  const height = 40;

  const safeData = (Array.isArray(data) ? data : [])
    .map((v) => Number(v))
    .filter((v) => Number.isFinite(v));

  if (safeData.length === 0) {
    return <svg width={width} height={height} aria-hidden />;
  }

  const max = Math.max(...safeData);
  const min = Math.min(...safeData);
  const range = max - min || 1;
  const xDenom = safeData.length > 1 ? safeData.length - 1 : 1;

  const points = safeData
    .map((v, i) => {
      const x = (i / xDenom) * width;
      const y = height - ((v - min) / range) * height;
      const nx = Number.isFinite(x) ? x : 0;
      const ny = Number.isFinite(y) ? y : height / 2;
      return `${nx},${ny}`;
    })
    .join(" ");

  return (
    <svg width={width} height={height} aria-hidden>
      <polyline points={points} fill="none" stroke={color} strokeWidth="2" />
    </svg>
  );
}