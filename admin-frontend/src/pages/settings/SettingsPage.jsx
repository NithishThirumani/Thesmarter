import React, { useState } from "react";
import { useOutletContext } from "react-router-dom";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import { useAuth } from "../../auth/authContext";
import * as authService from "../../auth/authService";

export default function SettingsPage() {
  const { tokens: t } = useTheme();
  const { addToast } = useOutletContext() || {};
  const { accessToken } = useAuth();
  const [currentPin, setCurrentPin] = useState("");
  const [newPin, setNewPin] = useState("");
  const [confirmPin, setConfirmPin] = useState("");
  const [changePinLoading, setChangePinLoading] = useState(false);
  const [changePinError, setChangePinError] = useState("");

  const handleChangePin = async (e) => {
    e.preventDefault();
    setChangePinError("");
    if (currentPin.length < 4 || newPin.length < 4) {
      setChangePinError("PIN must be at least 4 characters.");
      return;
    }
    if (newPin !== confirmPin) {
      setChangePinError("New PIN and confirmation do not match.");
      return;
    }
    if (newPin === currentPin) {
      setChangePinError("New PIN must be different from current PIN.");
      return;
    }
    setChangePinLoading(true);
    try {
      await authService.changePin(currentPin, newPin, accessToken);
      setCurrentPin("");
      setNewPin("");
      setConfirmPin("");
      addToast?.("success", "PIN changed", "Your PIN has been updated successfully.");
    } catch (err) {
      setChangePinError(err.message || "Failed to change PIN.");
    } finally {
      setChangePinLoading(false);
    }
  };

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader title="Settings" subtitle="Application and security settings" breadcrumb="Settings" />

      <Card title="Change PIN" style={{ maxWidth: 420 }}>
        <p style={{ margin: "0 0 20px", fontSize: TYPE.sm, color: t.textSecondary }}>
          Enter your current PIN and choose a new one. Use 4–12 digits.
        </p>
        <form onSubmit={handleChangePin} style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <div>
            <label style={{ display: "block", fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, marginBottom: 6 }}>Current PIN</label>
            <input
              type="password"
              inputMode="numeric"
              value={currentPin}
              onChange={(e) => setCurrentPin(e.target.value.replace(/\D/g, "").slice(0, 12))}
              placeholder="••••"
              style={{
                width: "100%",
                height: 42,
                padding: "0 12px",
                background: t.bgElevated,
                border: `1px solid ${t.border}`,
                borderRadius: 6,
                color: t.text,
                fontSize: TYPE.base,
              }}
            />
          </div>
          <div>
            <label style={{ display: "block", fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, marginBottom: 6 }}>New PIN</label>
            <input
              type="password"
              inputMode="numeric"
              value={newPin}
              onChange={(e) => setNewPin(e.target.value.replace(/\D/g, "").slice(0, 12))}
              placeholder="••••"
              style={{
                width: "100%",
                height: 42,
                padding: "0 12px",
                background: t.bgElevated,
                border: `1px solid ${t.border}`,
                borderRadius: 6,
                color: t.text,
                fontSize: TYPE.base,
              }}
            />
          </div>
          <div>
            <label style={{ display: "block", fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, marginBottom: 6 }}>Confirm new PIN</label>
            <input
              type="password"
              inputMode="numeric"
              value={confirmPin}
              onChange={(e) => setConfirmPin(e.target.value.replace(/\D/g, "").slice(0, 12))}
              placeholder="••••"
              style={{
                width: "100%",
                height: 42,
                padding: "0 12px",
                background: t.bgElevated,
                border: `1px solid ${t.border}`,
                borderRadius: 6,
                color: t.text,
                fontSize: TYPE.base,
              }}
            />
          </div>
          {changePinError && (
            <div style={{ fontSize: TYPE.sm, color: t.danger }}>{changePinError}</div>
          )}
          <Button type="submit" disabled={changePinLoading} variant="primary">
            {changePinLoading ? "Updating…" : "Change PIN"}
          </Button>
        </form>
      </Card>
    </div>
  );
}
