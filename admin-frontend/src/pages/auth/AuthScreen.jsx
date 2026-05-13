import React, { useState, useEffect, useRef } from "react";
import { useTheme } from "../../theme";
import { useMediaQuery } from "../../hooks/useMediaQuery";
import { TYPE } from "../../theme/typography";



export default function AuthScreenPage({ onLogin }) {
    const { tokens: t, mode, toggleMode } = useTheme();
    const narrow = useMediaQuery("(max-width: 420px)");
    const [step, setStep] = useState("email");
    const [email, setEmail] = useState("");
    const [otp, setOtp] = useState(["", "", "", "", "", ""]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [resendTimer, setResendTimer] = useState(0);
    const otpRefs = useRef([]);

    const handleSendOTP = () => {
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { setError("Enter a valid email address."); return; }
        setLoading(true); setError("");
        setTimeout(() => { setLoading(false); setStep("otp"); setResendTimer(10); }, 1200);
    };

    useEffect(() => {
        if (resendTimer > 0) { const timer = setTimeout(() => setResendTimer(s => s - 1), 1000); return () => clearTimeout(timer); }
    }, [resendTimer]);

    const handleOtpChange = (i, v) => {
        const val = v.replace(/\D/g, "").slice(-1);
        const next = [...otp]; next[i] = val; setOtp(next);
        if (val && i < 5) otpRefs.current[i + 1]?.focus();
    };
    const handleOtpKeyDown = (i, e) => {
        if (e.key === "Backspace" && !otp[i] && i > 0) otpRefs.current[i - 1]?.focus();
    };
    const handleVerify = () => {
        const code = otp.join("");
        if (code.length !== 6) { setError("Enter the full 6-digit OTP."); return; }
        setLoading(true); setError("");
        setTimeout(() => {
            setLoading(false);
            if (code.length === 6) {
                onLogin({ email, name: "Ethan Mercer", role: "Super Admin", token: "mock_access_token_xyz", refreshToken: "mock_refresh_xyz" });
            } else { setError("Invalid OTP. Try any 6 digits."); }
        }, 1200);
    };

    return (
        <div
            style={{
                minHeight: "100vh",
                background: t.loginBackdrop,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                fontFamily: TYPE.fontBody,
                position: "relative",
                overflow: "hidden",
            }}
        >
            <button
                type="button"
                onClick={toggleMode}
                title={mode === "dark" ? "Light theme" : "Dark theme"}
                style={{
                    position: "absolute",
                    top: 16,
                    right: 16,
                    zIndex: 5,
                    background: t.bgCard,
                    border: `1px solid ${t.border}`,
                    borderRadius: 10,
                    padding: "8px 12px",
                    cursor: "pointer",
                    fontSize: 18,
                    boxShadow: t.shadowElevated,
                }}
            >
                {mode === "dark" ? "☀️" : "🌙"}
            </button>
            <style>{`
          @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
          @keyframes spin { to { transform: rotate(360deg); } }
          @keyframes fadeUp { from { opacity:0; transform:translateY(16px) } to { opacity:1; transform:translateY(0) } }
          @keyframes slideInRight { from { opacity:0; transform:translateX(16px) } to { opacity:1; transform:translateX(0) } }
          * { box-sizing: border-box; margin: 0; padding: 0; }
          ::-webkit-scrollbar { width: 5px; height: 5px; }
          ::-webkit-scrollbar-track { background: var(--app-scrollbar-track, #07080a); }
          ::-webkit-scrollbar-thumb { background: var(--app-scrollbar-thumb, #23272f); border-radius: 3px; }
          ::-webkit-scrollbar-thumb:hover { background: var(--app-scrollbar-thumb-hover, #2e3340); }
          input::placeholder { color: var(--app-placeholder, #515866) !important; }
          select option { background: ${mode === "dark" ? "#0f1114" : "#fafafa"}; color: ${t.text}; }
        `}</style>

            {/* Subtle dot-grid background */}
            <div style={{
                position: "absolute", inset: 0,
            backgroundImage: `radial-gradient(circle, ${t.loginDotGrid} 1px, transparent 1px)`,
                backgroundSize: "28px 28px",
            }} />
            <div style={{
                position: "absolute", top: "15%", left: "50%", transform: "translateX(-50%)",
                width: 600, height: 500,
                background: `radial-gradient(ellipse at center, ${t.loginOrbGlow} 0%, transparent 62%)`,
                pointerEvents: "none",
            }} />

            <div style={{ animation: "fadeUp 0.4s ease", position: "relative", width: "100%", maxWidth: 420, margin: `0 ${narrow ? 14 : 20}px`, paddingTop: 8 }}>
                {/* Logo */}
                <div style={{ textAlign: "center", marginBottom: 40 }}>
                    <div style={{ display: "inline-flex", alignItems: "center", gap: 10, marginBottom: 10 }}>
                        <div style={{
                            width: 40, height: 40, background: t.accent, borderRadius: 8,
                            display: "flex", alignItems: "center", justifyContent: "center",
                            boxShadow: `0 0 0 1px ${t.accent}40, 0 8px 24px ${t.accent}30`,
                        }}>
                            <span style={{ fontSize: 20, fontWeight: 900, color: "#000", fontFamily: TYPE.fontDisplay }}>S</span>
                        </div>
                        <span style={{ fontFamily: TYPE.fontDisplay, fontSize: 26, fontWeight: TYPE.black, color: t.text, letterSpacing: "-0.04em" }}>theSmartr</span>
                    </div>
                    <div style={{ fontSize: TYPE.xs, color: t.textMuted, letterSpacing: "0.12em", textTransform: "uppercase", fontWeight: TYPE.medium }}>
                        Enterprise ERP · Super Admin Console
                    </div>
                </div>

                <div style={{
                    background: t.bgCard,
                    border: `1px solid ${t.loginCardBorder}`,
                    borderRadius: 12,
                    padding: narrow ? "22px 18px 20px" : "32px 32px 28px",
                    boxShadow: t.loginCardShadow,
                }}>
                    {step === "email" && (
                        <div key="email">
                            <h2 style={{ margin: "0 0 6px", fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: "-0.02em" }}>Welcome back</h2>
                            <p style={{ margin: "0 0 28px", fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                                Enter your admin email to receive a verification code.
                            </p>
                            <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>Email Address</label>
                                <div style={{ position: "relative" }}>
                                    <span style={{ position: "absolute", left: 12, top: "50%", transform: "translateY(-50%)", color: t.textMuted, fontSize: 14, pointerEvents: "none" }}>✉</span>
                                    <input type="email" value={email} onChange={e => setEmail(e.target.value)}
                                        placeholder="admin@yourcompany.com"
                                        onKeyDown={e => e.key === "Enter" && handleSendOTP()}
                                        style={{
                                            width: "100%", height: 42, padding: "0 12px 0 38px",
                                            background: t.bgElevated, border: `1px solid ${t.border}`,
                                            borderRadius: 6, color: t.text, fontSize: TYPE.base,
                                            fontFamily: TYPE.fontBody, outline: "none",
                                        }} />
                                </div>
                            </div>
                            {error && <div style={{ marginTop: 10, fontSize: TYPE.sm, color: t.danger, fontWeight: TYPE.medium }}>{error}</div>}
                            <button onClick={handleSendOTP} disabled={loading}
                                style={{
                                    width: "100%", height: 42, marginTop: 20, background: loading ? t.accentDim : t.accent,
                                    border: "none", borderRadius: 6, cursor: loading ? "not-allowed" : "pointer",
                                    fontFamily: TYPE.fontBody, fontWeight: TYPE.semibold, fontSize: TYPE.base,
                                    color: "#000", letterSpacing: "0.01em", transition: "background 0.15s",
                                }}>
                                {loading ? "Sending verification code…" : "Continue with Email →"}
                            </button>
                        </div>
                    )}

                    {step === "otp" && (
                        <div key="otp">
                            <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 6 }}>
                                <button onClick={() => setStep("email")} style={{
                                    background: t.bgHover, border: `1px solid ${t.border}`, color: t.textSecondary,
                                    cursor: "pointer", fontSize: 14, width: 30, height: 30, borderRadius: 6,
                                    display: "flex", alignItems: "center", justifyContent: "center",
                                }}>←</button>
                                <h2 style={{ margin: 0, fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: "-0.02em" }}>Check your email</h2>
                            </div>
                            <p style={{ margin: "0 0 28px", fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                                Code sent to <strong style={{ color: t.accent, fontWeight: TYPE.semibold }}>{email}</strong>
                                <br />
                                <span style={{ fontSize: TYPE.sm, color: t.textMuted }}>Use any 6-digit code for demo access.</span>
                            </p>
                            <div style={{ display: "flex", gap: 8, justifyContent: "center", marginBottom: 24 }}>
                                {otp.map((digit, i) => (
                                    <input key={i} ref={el => otpRefs.current[i] = el} maxLength={1} value={digit}
                                        onChange={e => handleOtpChange(i, e.target.value)}
                                        onKeyDown={e => handleOtpKeyDown(i, e)}
                                        style={{
                                            width: 46, height: 54, textAlign: "center",
                                            fontSize: 24, fontWeight: TYPE.bold,
                                            background: digit ? t.bgSubtle : t.bgElevated,
                                            border: `1.5px solid ${digit ? t.accent : t.border}`,
                                            borderRadius: 8, color: t.text, outline: "none",
                                            fontFamily: TYPE.fontMono,
                                            transition: "border-color 0.15s, background 0.15s",
                                            boxShadow: digit ? `0 0 0 3px ${t.accent}20` : "none",
                                        }} />
                                ))}
                            </div>
                            {error && <div style={{ marginBottom: 12, fontSize: TYPE.sm, color: t.danger, textAlign: "center", fontWeight: TYPE.medium }}>{error}</div>}
                            <button onClick={handleVerify} disabled={loading}
                                style={{
                                    width: "100%", height: 42, background: loading ? t.accentDim : t.accent,
                                    border: "none", borderRadius: 6, cursor: loading ? "not-allowed" : "pointer",
                                    fontFamily: TYPE.fontBody, fontWeight: TYPE.semibold, fontSize: TYPE.base,
                                    color: "#000", transition: "background 0.15s",
                                }}>
                                {loading ? "Verifying…" : "Verify & Enter Admin Console →"}
                            </button>
                            <div style={{ textAlign: "center", marginTop: 16, fontSize: TYPE.sm, color: t.textMuted }}>
                                {resendTimer > 0
                                    ? `Resend available in ${resendTimer}s`
                                    : <span style={{ color: t.accent, cursor: "pointer", fontWeight: TYPE.medium }} onClick={handleSendOTP}>Resend code</span>
                                }
                            </div>
                        </div>
                    )}
                </div>

                <div style={{ textAlign: "center", marginTop: 20, fontSize: TYPE.xs, color: t.textMuted, letterSpacing: "0.02em" }}>
                    Secured with OAuth2 · TLS encrypted · Session-managed
                </div>
            </div>
        </div>
    );
};
