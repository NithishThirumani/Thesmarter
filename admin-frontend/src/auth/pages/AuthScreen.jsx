/**
 * Admin login flow: Email → PIN → OTP. Uses authService and authContext only.
 */

import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useTheme } from '../../theme';
import { useMediaQuery } from '../../hooks/useMediaQuery';
import { TYPE } from '../../theme/typography';
import * as authService from '../authService';
import { useAuth } from '../authContext';

const STEPS = { email: 'email', pin: 'pin', otp: 'otp', forgotRequest: 'forgotRequest', forgotReset: 'forgotReset' };

export default function AuthScreen() {
  const { finishLogin } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/dashboard';
  const { tokens: t, mode, toggleMode } = useTheme();
  const narrow = useMediaQuery('(max-width: 420px)');
  const [step, setStep] = useState(STEPS.email);
  const [email, setEmail] = useState('');
  const [pin, setPin] = useState('');
  const [otp, setOtp] = useState(['', '', '', '', '', '']);
  const [newPin, setNewPin] = useState('');
  const [confirmPin, setConfirmPin] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [resendTimer, setResendTimer] = useState(0);
  /** Shown when API returns otp_dev_hint (fixed OTP in development). */
  const [otpDevHint, setOtpDevHint] = useState('');
  /** Whether PIN reset was opened from the PIN step (original) or from the email step (alternate path). */
  const [forgotPinEntry, setForgotPinEntry] = useState('pin');
  const otpRefs = useRef([]);

  useEffect(() => {
    if (resendTimer > 0) {
      const timer = setTimeout(() => setResendTimer((s) => s - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [resendTimer]);

  const handleEmailSubmit = async () => {
    const trimmed = email.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
      setError('Enter a valid email address.');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const loginRes = await authService.login(trimmed);
      setOtpDevHint(loginRes.otp_dev_hint || '');
      setStep(STEPS.pin);
    } catch (e) {
      setError(e.message || 'Request failed.');
    } finally {
      setLoading(false);
    }
  };

  const handlePinSubmit = async () => {
    if (!pin || pin.length < 4) {
      setError('Enter your PIN (at least 4 characters).');
      return;
    }
    setLoading(true);
    setError('');
    try {
      await authService.verifyPin(email.trim().toLowerCase(), pin);
      setStep(STEPS.otp);
      setResendTimer(30);
    } catch (e) {
      setError(e.message || 'Invalid PIN.');
    } finally {
      setLoading(false);
    }
  };

  const handleResendOtp = async () => {
    if (resendTimer > 0) return;
    setLoading(true);
    setError('');
    try {
      const loginRes = await authService.login(email.trim().toLowerCase());
      setOtpDevHint(loginRes.otp_dev_hint || '');
      setResendTimer(30);
    } catch (e) {
      setError(e.message || 'Resend failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleOtpChange = (i, v) => {
    const val = v.replace(/\D/g, '').slice(-1);
    const next = [...otp];
    next[i] = val;
    setOtp(next);
    if (val && i < 5) otpRefs.current[i + 1]?.focus();
  };

  const handleOtpKeyDown = (i, e) => {
    if (e.key === 'Backspace' && !otp[i] && i > 0) otpRefs.current[i - 1]?.focus();
  };

  const handleVerifyOtp = async () => {
    const code = otp.join('');
    if (code.length !== 6) {
      setError('Enter the full 6-digit OTP.');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const data = await authService.verifyOtp(email.trim().toLowerCase(), code);
      finishLogin(data);
      navigate(from, { replace: true });
    } catch (e) {
      setError(e.message || 'Invalid or expired OTP.');
    } finally {
      setLoading(false);
    }
  };

  const goBack = () => {
    setError('');
    if (step === STEPS.pin) setStep(STEPS.email);
    else if (step === STEPS.otp) setStep(STEPS.pin);
    else if (step === STEPS.forgotReset) {
      setStep(forgotPinEntry === 'email' ? STEPS.forgotRequest : STEPS.pin);
    } else if (step === STEPS.forgotRequest) {
      setStep(STEPS.email);
    }
  };

  const handleForgotPinClick = async () => {
    setError('');
    setForgotPinEntry('pin');
    setLoading(true);
    try {
      const fp = await authService.forgotPin(email.trim().toLowerCase());
      setOtpDevHint(fp.otp_dev_hint || '');
      setStep(STEPS.forgotReset);
      setOtp(['', '', '', '', '', '']);
      setNewPin('');
      setConfirmPin('');
      setResendTimer(30);
    } catch (e) {
      setError(e.message || 'Could not send reset OTP.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotEmailScreenSubmit = async () => {
    const trimmed = email.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
      setError('Enter a valid email address.');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const fp = await authService.forgotPin(trimmed.toLowerCase());
      setOtpDevHint(fp.otp_dev_hint || '');
      setStep(STEPS.forgotReset);
      setOtp(['', '', '', '', '', '']);
      setNewPin('');
      setConfirmPin('');
      setResendTimer(30);
    } catch (e) {
      setError(e.message || 'Could not send reset OTP.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotResetResendCode = async () => {
    if (resendTimer > 0) return;
    setLoading(true);
    setError('');
    try {
      const fp = await authService.forgotPin(email.trim().toLowerCase());
      setOtpDevHint(fp.otp_dev_hint || '');
      setResendTimer(30);
    } catch (e) {
      setError(e.message || 'Resend failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetPinSubmit = async () => {
    const code = otp.join('');
    if (code.length !== 6) {
      setError('Enter the full 6-digit OTP.');
      return;
    }
    if (newPin.length < 4) {
      setError('New PIN must be at least 4 characters.');
      return;
    }
    if (newPin !== confirmPin) {
      setError('New PIN and confirmation do not match.');
      return;
    }
    setLoading(true);
    setError('');
    try {
      await authService.resetPin(email.trim().toLowerCase(), code, newPin);
      setStep(STEPS.pin);
      setForgotPinEntry('pin');
      setPin('');
      setError('');
      setNewPin('');
      setConfirmPin('');
      setOtp(['', '', '', '', '', '']);
      setResendTimer(0);
    } catch (e) {
      setError(e.message || 'Reset failed.');
    } finally {
      setLoading(false);
    }
  };

  const scrollTrackFallback = mode === 'dark' ? '#0a0a0a' : '#f4f4f5';
  const scrollThumbFallback = mode === 'dark' ? '#2a2a2a' : '#cbd5e1';

  const loginBackgroundLayers = [
    `radial-gradient(ellipse at center, ${t.loginOrbGlow} 0%, transparent 62%)`,
    `radial-gradient(circle, ${t.loginDotGrid} 1px, transparent 1px)`,
  ].join(', ');

  return (
    <div
      data-theme={mode}
      style={{
        minHeight: '100vh',
        width: '100%',
        boxSizing: 'border-box',
        backgroundColor: t.loginBackdrop,
        backgroundImage: loginBackgroundLayers,
        backgroundSize: '600px 500px, 28px 28px',
        backgroundRepeat: 'no-repeat, repeat',
        backgroundPosition: 'center 15%, 0 0',
        color: t.text,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontFamily: TYPE.fontBody,
        position: 'relative',
        overflow: 'hidden',
        transition: 'background-color 0.25s ease, color 0.25s ease',
      }}
    >
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
        @keyframes fadeUp { from { opacity:0; transform:translateY(16px) } to { opacity:1; transform:translateY(0) } }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--app-scrollbar-track, ${scrollTrackFallback}); }
        ::-webkit-scrollbar-thumb { background: var(--app-scrollbar-thumb, ${scrollThumbFallback}); border-radius: 3px; }
        input::placeholder { color: var(--app-placeholder, ${mode === 'dark' ? '#737373' : '#71717a'}) !important; }
      `}</style>

      <button
        type="button"
        onClick={toggleMode}
        title={mode === 'dark' ? 'Light theme' : 'Dark theme'}
        style={{
          position: 'absolute',
          top: 16,
          right: 16,
          zIndex: 5,
          background: t.bgCard,
          border: `1px solid ${t.border}`,
          borderRadius: 10,
          padding: '8px 12px',
          cursor: 'pointer',
          fontSize: 18,
          boxShadow: t.shadowElevated,
        }}
      >
        {mode === 'dark' ? '☀️' : '🌙'}
      </button>

      <div style={{ animation: 'fadeUp 0.4s ease', position: 'relative', zIndex: 1, width: '100%', maxWidth: 420, margin: `0 ${narrow ? 14 : 20}px`, paddingTop: 8 }}>
        <div style={{ textAlign: 'center', marginBottom: 40 }}>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 10, marginBottom: 10 }}>
            <div
              style={{
                width: 40,
                height: 40,
                background: t.accent,
                borderRadius: 8,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                boxShadow: `0 0 0 1px ${t.accent}40, 0 8px 24px ${t.accent}30`,
              }}
            >
              <span style={{ fontSize: 20, fontWeight: 900, color: '#000', fontFamily: TYPE.fontDisplay }}>S</span>
            </div>
            <span style={{ fontFamily: TYPE.fontDisplay, fontSize: 26, fontWeight: TYPE.black, color: t.text, letterSpacing: '-0.04em' }}>theSmartr</span>
          </div>
          <div style={{ fontSize: TYPE.xs, color: t.textMuted, letterSpacing: '0.12em', textTransform: 'uppercase', fontWeight: TYPE.medium }}>
            Enterprise ERP · Super Admin Console
          </div>
        </div>

        <div
          style={{
            background: t.bgCard,
            border: `1px solid ${t.loginCardBorder}`,
            borderRadius: 12,
            padding: narrow ? '22px 18px 20px' : '32px 32px 28px',
            boxShadow: t.loginCardShadow,
          }}
        >
          {step === STEPS.email && (
            <div key="email">
              <h2 style={{ margin: '0 0 6px', fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: '-0.02em' }}>Welcome back</h2>
              <p style={{ margin: '0 0 28px', fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                Enter your admin email. This console uses a <strong style={{ color: t.text }}>PIN</strong>, not a password.
              </p>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: '0.06em', textTransform: 'uppercase' }}>Email Address</label>
                <div style={{ position: 'relative' }}>
                  <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: t.textMuted, fontSize: 14, pointerEvents: 'none' }}>✉</span>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="admin@yourcompany.com"
                    onKeyDown={(e) => e.key === 'Enter' && handleEmailSubmit()}
                    style={{
                      width: '100%',
                      height: 42,
                      padding: '0 12px 0 38px',
                      background: t.bgElevated,
                      border: `1px solid ${t.border}`,
                      borderRadius: 6,
                      color: t.text,
                      fontSize: TYPE.base,
                      fontFamily: TYPE.fontBody,
                      outline: 'none',
                    }}
                  />
                </div>
              </div>
              {error && <div style={{ marginTop: 10, fontSize: TYPE.sm, color: t.danger, fontWeight: TYPE.medium }}>{error}</div>}
              <button
                onClick={handleEmailSubmit}
                disabled={loading}
                style={{
                  width: '100%',
                  height: 42,
                  marginTop: 20,
                  background: loading ? t.accentDim : t.accent,
                  border: 'none',
                  borderRadius: 6,
                  cursor: loading ? 'not-allowed' : 'pointer',
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                  fontSize: TYPE.base,
                  color: '#000',
                  letterSpacing: '0.01em',
                  transition: 'background 0.15s',
                }}
              >
                {loading ? 'Sending…' : 'Continue →'}
              </button>
              <div style={{ marginTop: 16, textAlign: 'center', fontSize: TYPE.sm, color: t.textMuted, lineHeight: 1.5 }}>
                Forgot your PIN?{' '}
                <button
                  type="button"
                  onClick={() => {
                    setError('');
                    setForgotPinEntry('email');
                    setStep(STEPS.forgotRequest);
                  }}
                  style={{
                    background: 'none',
                    border: 'none',
                    color: t.accent,
                    cursor: 'pointer',
                    fontSize: TYPE.sm,
                    textDecoration: 'underline',
                    fontFamily: TYPE.fontBody,
                    fontWeight: TYPE.semibold,
                  }}
                >
                  Enter email to get a reset code
                </button>
              </div>
            </div>
          )}

          {step === STEPS.forgotRequest && (
            <div key="forgotRequest">
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                <button
                  type="button"
                  onClick={goBack}
                  style={{
                    background: t.bgHover,
                    border: `1px solid ${t.border}`,
                    color: t.textSecondary,
                    cursor: 'pointer',
                    fontSize: 14,
                    width: 30,
                    height: 30,
                    borderRadius: 6,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  ←
                </button>
                <h2 style={{ margin: 0, fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: '-0.02em' }}>Reset PIN</h2>
              </div>
              <p style={{ margin: '0 0 20px', fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                Enter the admin email for your account. We will send a one-time code so you can choose a new PIN.
              </p>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: '0.06em', textTransform: 'uppercase' }}>Email Address</label>
                <div style={{ position: 'relative' }}>
                  <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: t.textMuted, fontSize: 14, pointerEvents: 'none' }}>✉</span>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="admin@yourcompany.com"
                    onKeyDown={(e) => e.key === 'Enter' && handleForgotEmailScreenSubmit()}
                    style={{
                      width: '100%',
                      height: 42,
                      padding: '0 12px 0 38px',
                      background: t.bgElevated,
                      border: `1px solid ${t.border}`,
                      borderRadius: 6,
                      color: t.text,
                      fontSize: TYPE.base,
                      fontFamily: TYPE.fontBody,
                      outline: 'none',
                    }}
                  />
                </div>
              </div>
              {error && <div style={{ marginTop: 10, fontSize: TYPE.sm, color: t.danger, fontWeight: TYPE.medium }}>{error}</div>}
              <button
                type="button"
                onClick={handleForgotEmailScreenSubmit}
                disabled={loading}
                style={{
                  width: '100%',
                  height: 42,
                  marginTop: 20,
                  background: loading ? t.accentDim : t.accent,
                  border: 'none',
                  borderRadius: 6,
                  cursor: loading ? 'not-allowed' : 'pointer',
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                  fontSize: TYPE.base,
                  color: '#000',
                  letterSpacing: '0.01em',
                  transition: 'background 0.15s',
                }}
              >
                {loading ? 'Sending…' : 'Send reset code →'}
              </button>
            </div>
          )}

          {step === STEPS.pin && (
            <div key="pin">
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                <button
                  type="button"
                  onClick={goBack}
                  style={{
                    background: t.bgHover,
                    border: `1px solid ${t.border}`,
                    color: t.textSecondary,
                    cursor: 'pointer',
                    fontSize: 14,
                    width: 30,
                    height: 30,
                    borderRadius: 6,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  ←
                </button>
                <h2 style={{ margin: 0, fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: '-0.02em' }}>Enter PIN</h2>
              </div>
              <p style={{ margin: '0 0 12px', fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                Enter your PIN for <strong style={{ color: t.accent, fontWeight: TYPE.semibold }}>{email}</strong>
              </p>
              {otpDevHint ? (
                <div
                  style={{
                    marginBottom: 16,
                    padding: '10px 12px',
                    borderRadius: 8,
                    background: t.accentSubtle || 'rgba(59,130,246,0.12)',
                    border: `1px solid ${t.border}`,
                    fontSize: TYPE.sm,
                    color: t.textSecondary,
                    lineHeight: 1.5,
                  }}
                >
                  {otpDevHint}
                </div>
              ) : null}
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: '0.06em', textTransform: 'uppercase' }}>PIN</label>
                <input
                  type="password"
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  value={pin}
                  onChange={(e) => setPin(e.target.value.replace(/\D/g, '').slice(0, 12))}
                  placeholder="••••"
                  onKeyDown={(e) => e.key === 'Enter' && handlePinSubmit()}
                  style={{
                    width: '100%',
                    height: 42,
                    padding: '0 12px',
                    background: t.bgElevated,
                    border: `1px solid ${t.border}`,
                    borderRadius: 6,
                    color: t.text,
                    fontSize: TYPE.base,
                    fontFamily: TYPE.fontBody,
                    outline: 'none',
                  }}
                />
              </div>
              {error && <div style={{ marginTop: 10, fontSize: TYPE.sm, color: t.danger, fontWeight: TYPE.medium }}>{error}</div>}
              <button
                onClick={handlePinSubmit}
                disabled={loading}
                style={{
                  width: '100%',
                  height: 42,
                  marginTop: 20,
                  background: loading ? t.accentDim : t.accent,
                  border: 'none',
                  borderRadius: 6,
                  cursor: loading ? 'not-allowed' : 'pointer',
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                  fontSize: TYPE.base,
                  color: '#000',
                  transition: 'background 0.15s',
                }}
              >
                {loading ? 'Verifying…' : 'Continue →'}
              </button>
              <div style={{ marginTop: 16, textAlign: 'center' }}>
                <button
                  type="button"
                  onClick={handleForgotPinClick}
                  disabled={loading}
                  style={{
                    background: 'none',
                    border: 'none',
                    color: t.accent,
                    cursor: loading ? 'not-allowed' : 'pointer',
                    fontSize: TYPE.sm,
                    textDecoration: 'underline',
                    fontFamily: TYPE.fontBody,
                  }}
                >
                  Forgot PIN? (no account password)
                </button>
              </div>
            </div>
          )}

          {step === STEPS.forgotReset && (
            <div key="forgotReset">
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                <button type="button" onClick={goBack} style={{ background: t.bgHover, border: `1px solid ${t.border}`, color: t.textSecondary, cursor: 'pointer', fontSize: 14, width: 30, height: 30, borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>←</button>
                <h2 style={{ margin: 0, fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay }}>Reset PIN</h2>
              </div>
              <p style={{ margin: '0 0 20px', fontSize: TYPE.base, color: t.textSecondary }}>
                Enter the 6-digit code sent to <strong style={{ color: t.accent }}>{email}</strong> and your new PIN. If email is not configured, use the development code shown below.
              </p>
              {otpDevHint ? (
                <div
                  style={{
                    margin: '-8px 0 16px',
                    padding: '10px 12px',
                    borderRadius: 8,
                    background: t.accentSubtle || 'rgba(59,130,246,0.12)',
                    border: `1px solid ${t.border}`,
                    fontSize: TYPE.sm,
                    color: t.textSecondary,
                    lineHeight: 1.5,
                  }}
                >
                  {otpDevHint}
                </div>
              ) : null}
              <div style={{ display: 'flex', gap: narrow ? 6 : 8, justifyContent: 'center', marginBottom: 20, flexWrap: 'wrap' }}>
                {otp.map((digit, i) => (
                  <input
                    key={i}
                    ref={(el) => (otpRefs.current[i] = el)}
                    maxLength={1}
                    value={digit}
                    onChange={(e) => handleOtpChange(i, e.target.value)}
                    onKeyDown={(e) => handleOtpKeyDown(i, e)}
                    style={{ width: narrow ? 40 : 46, height: narrow ? 44 : 48, textAlign: 'center', fontSize: narrow ? 18 : 20, fontWeight: TYPE.bold, background: t.bgElevated, border: `1.5px solid ${t.border}`, borderRadius: 8, color: t.text, outline: 'none', fontFamily: TYPE.fontMono }}
                  />
                ))}
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginBottom: 16 }}>
                <input type="password" inputMode="numeric" placeholder="New PIN (4–12 digits)" value={newPin} onChange={(e) => setNewPin(e.target.value.replace(/\D/g, '').slice(0, 12))} style={{ width: '100%', height: 42, padding: '0 12px', background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text, fontSize: TYPE.base }} />
                <input type="password" inputMode="numeric" placeholder="Confirm new PIN" value={confirmPin} onChange={(e) => setConfirmPin(e.target.value.replace(/\D/g, '').slice(0, 12))} style={{ width: '100%', height: 42, padding: '0 12px', background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text, fontSize: TYPE.base }} />
              </div>
              {error && <div style={{ marginBottom: 10, fontSize: TYPE.sm, color: t.danger }}>{error}</div>}
              <button onClick={handleResetPinSubmit} disabled={loading} style={{ width: '100%', height: 42, background: loading ? t.accentDim : t.accent, border: 'none', borderRadius: 6, cursor: loading ? 'not-allowed' : 'pointer', fontFamily: TYPE.fontBody, fontWeight: TYPE.semibold, fontSize: TYPE.base, color: '#000' }}>
                {loading ? 'Resetting…' : 'Reset PIN'}
              </button>
              <div style={{ textAlign: 'center', marginTop: 14, fontSize: TYPE.sm, color: t.textMuted }}>
                {resendTimer > 0 ? (
                  `Resend code in ${resendTimer}s`
                ) : (
                  <span
                    role="button"
                    tabIndex={0}
                    onClick={handleForgotResetResendCode}
                    onKeyDown={(e) => e.key === 'Enter' && handleForgotResetResendCode()}
                    style={{ color: t.accent, cursor: 'pointer', fontWeight: TYPE.medium }}
                  >
                    Resend code
                  </span>
                )}
              </div>
            </div>
          )}

          {step === STEPS.otp && (
            <div key="otp">
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                <button
                  type="button"
                  onClick={goBack}
                  style={{
                    background: t.bgHover,
                    border: `1px solid ${t.border}`,
                    color: t.textSecondary,
                    cursor: 'pointer',
                    fontSize: 14,
                    width: 30,
                    height: 30,
                    borderRadius: 6,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  ←
                </button>
                <h2 style={{ margin: 0, fontSize: TYPE.xl, fontWeight: TYPE.bold, color: t.text, fontFamily: TYPE.fontDisplay, letterSpacing: '-0.02em' }}>Check your email</h2>
              </div>
              <p style={{ margin: '0 0 12px', fontSize: TYPE.base, color: t.textSecondary, lineHeight: 1.6 }}>
                Code sent to <strong style={{ color: t.accent, fontWeight: TYPE.semibold }}>{email}</strong>
              </p>
              {otpDevHint ? (
                <div
                  style={{
                    marginBottom: 20,
                    padding: '10px 12px',
                    borderRadius: 8,
                    background: t.accentSubtle || 'rgba(59,130,246,0.12)',
                    border: `1px solid ${t.border}`,
                    fontSize: TYPE.sm,
                    color: t.textSecondary,
                    lineHeight: 1.5,
                  }}
                >
                  {otpDevHint}
                </div>
              ) : null}
              <div style={{ display: 'flex', gap: narrow ? 6 : 8, justifyContent: 'center', marginBottom: 24, flexWrap: 'wrap' }}>
                {otp.map((digit, i) => (
                  <input
                    key={i}
                    ref={(el) => (otpRefs.current[i] = el)}
                    maxLength={1}
                    value={digit}
                    onChange={(e) => handleOtpChange(i, e.target.value)}
                    onKeyDown={(e) => handleOtpKeyDown(i, e)}
                    style={{
                      width: narrow ? 40 : 46,
                      height: narrow ? 48 : 54,
                      textAlign: 'center',
                      fontSize: narrow ? 20 : 24,
                      fontWeight: TYPE.bold,
                      background: digit ? t.bgSubtle : t.bgElevated,
                      border: `1.5px solid ${digit ? t.accent : t.border}`,
                      borderRadius: 8,
                      color: t.text,
                      outline: 'none',
                      fontFamily: TYPE.fontMono,
                      transition: 'border-color 0.15s, background 0.15s',
                      boxShadow: digit ? `0 0 0 3px ${t.accent}20` : 'none',
                    }}
                  />
                ))}
              </div>
              {error && <div style={{ marginBottom: 12, fontSize: TYPE.sm, color: t.danger, textAlign: 'center', fontWeight: TYPE.medium }}>{error}</div>}
              <button
                onClick={handleVerifyOtp}
                disabled={loading}
                style={{
                  width: '100%',
                  height: 42,
                  background: loading ? t.accentDim : t.accent,
                  border: 'none',
                  borderRadius: 6,
                  cursor: loading ? 'not-allowed' : 'pointer',
                  fontFamily: TYPE.fontBody,
                  fontWeight: TYPE.semibold,
                  fontSize: TYPE.base,
                  color: '#000',
                  transition: 'background 0.15s',
                }}
              >
                {loading ? 'Verifying…' : 'Verify & Enter Admin Console →'}
              </button>
              <div style={{ textAlign: 'center', marginTop: 16, fontSize: TYPE.sm, color: t.textMuted }}>
                {resendTimer > 0 ? (
                  `Resend available in ${resendTimer}s`
                ) : (
                  <span style={{ color: t.accent, cursor: 'pointer', fontWeight: TYPE.medium }} onClick={handleResendOtp}>
                    Resend code
                  </span>
                )}
              </div>
            </div>
          )}
        </div>

        <div style={{ textAlign: 'center', marginTop: 20, fontSize: TYPE.xs, color: t.textMuted, letterSpacing: '0.02em' }}>
          Powered By Bizwy
        </div>
      </div>
    </div>
  );
}
