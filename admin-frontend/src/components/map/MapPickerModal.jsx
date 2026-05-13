import React, { useEffect, useRef, useState } from "react";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";

// Lightweight Leaflet-based map picker (CDN) to capture lat/lng by clicking the map.
export default function MapPickerModal({
  open,
  onClose,
  initialLat = 12.9716,
  initialLng = 77.5946,
  onPick,
}) {
  const { tokens: t } = useTheme();
  const mapEl = useRef(null);
  const mapRef = useRef(null);
  const markerRef = useRef(null);
  const [loading, setLoading] = useState(false);
  const [picked, setPicked] = useState({ lat: initialLat, lng: initialLng });

  useEffect(() => {
    if (!open) return;
    setPicked({ lat: initialLat, lng: initialLng });
  }, [open, initialLat, initialLng]);

  useEffect(() => {
    if (!open) return;

    let cancelled = false;

    const loadLeaflet = async () => {
      setLoading(true);
      try {
        // Inject CSS once
        if (!document.querySelector('link[data-lob="leaflet-css"]')) {
          const link = document.createElement("link");
          link.rel = "stylesheet";
          link.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
          link.setAttribute("data-lob", "leaflet-css");
          document.head.appendChild(link);
        }

        // Inject JS once
        if (!window.L) {
          await new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Failed to load map library."));
            document.body.appendChild(script);
          });
        }

        if (window.L && !cancelled) {
          // Fix marker icons when using CDN bundles
          delete window.L.Icon.Default.prototype._getIconUrl;
          window.L.Icon.Default.mergeOptions({
            iconRetinaUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
            iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
            shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
          });
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    loadLeaflet().catch(() => {
      if (!cancelled) setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, [open]);

  useEffect(() => {
    if (!open) return;
    if (loading) return;
    if (!window.L) return;
    if (!mapEl.current) return;

    if (mapRef.current) return;

    const L = window.L;
    const map = L.map(mapEl.current).setView([picked.lat, picked.lng], 12);
    mapRef.current = map;

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    const marker = L.marker([picked.lat, picked.lng], { draggable: false }).addTo(map);
    markerRef.current = marker;

    map.on("click", (e) => {
      const lat = e.latlng.lat;
      const lng = e.latlng.lng;
      setPicked({ lat, lng });
      if (markerRef.current) {
        markerRef.current.setLatLng([lat, lng]);
      }
    });

    return () => {
      try {
        map.off();
        map.remove();
      } catch (_) {}
      mapRef.current = null;
      markerRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, loading]);

  if (!open) return null;

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        background: "rgba(0,0,0,0.75)",
        zIndex: 1200,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        backdropFilter: "blur(6px)",
      }}
      onClick={onClose}
    >
      <div
        style={{
          width: 860,
          maxWidth: "95vw",
          background: t.bgCard,
          border: `1px solid ${t.borderStrong}`,
          borderRadius: 10,
          overflow: "hidden",
          boxShadow: "0 40px 100px rgba(0,0,0,0.9)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div style={{ padding: "18px 24px", borderBottom: `1px solid ${t.border}`, display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <div style={{ fontFamily: TYPE.fontDisplay, fontWeight: TYPE.bold, fontSize: TYPE.lg, color: t.text }}>Pick location</div>
          <button
            onClick={onClose}
            style={{
              background: t.bgHover,
              border: `1px solid ${t.border}`,
              color: t.textMuted,
              cursor: "pointer",
              fontSize: 16,
              lineHeight: 1,
              width: 28,
              height: 28,
              borderRadius: 6,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
            }}
          >
            ×
          </button>
        </div>

        <div style={{ padding: 16, display: "grid", gridTemplateColumns: "1fr 260px", gap: 16 }}>
          <div
            ref={mapEl}
            style={{
              height: 420,
              borderRadius: 10,
              border: `1px solid ${t.border}`,
              background: t.bgElevated,
              overflow: "hidden",
            }}
          />

          <div style={{ padding: 8, display: "flex", flexDirection: "column", gap: 12 }}>
            <div>
              <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontWeight: TYPE.bold, textTransform: "uppercase", letterSpacing: "0.07em" }}>Selected</div>
              <div style={{ marginTop: 8, color: t.text, fontFamily: TYPE.fontMono, fontSize: TYPE.sm }}>
                {picked.lat.toFixed(6)}, {picked.lng.toFixed(6)}
              </div>
            </div>

            <div>
              <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontWeight: TYPE.bold, textTransform: "uppercase", letterSpacing: "0.07em", marginBottom: 6 }}>Lat / Lng</div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                <input
                  value={picked.lat}
                  onChange={(e) => setPicked((p) => ({ ...p, lat: Number(e.target.value) }))}
                  style={{ height: 38, background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text, padding: "0 10px", fontFamily: TYPE.fontMono }}
                />
                <input
                  value={picked.lng}
                  onChange={(e) => setPicked((p) => ({ ...p, lng: Number(e.target.value) }))}
                  style={{ height: 38, background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text, padding: "0 10px", fontFamily: TYPE.fontMono }}
                />
              </div>
            </div>

            <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: "auto" }}>
              <button
                type="button"
                onClick={onClose}
                disabled={loading}
                style={{
                  background: "transparent",
                  color: t.textSecondary,
                  border: `1px solid ${t.border}`,
                  borderRadius: 6,
                  height: 36,
                  padding: "0 14px",
                  cursor: loading ? "not-allowed" : "pointer",
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                }}
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  onPick(picked.lat, picked.lng);
                  onClose();
                }}
                disabled={loading}
                style={{
                  background: t.accent,
                  color: "#000",
                  border: "none",
                  borderRadius: 6,
                  height: 36,
                  padding: "0 14px",
                  cursor: loading ? "not-allowed" : "pointer",
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                }}
              >
                Use
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

