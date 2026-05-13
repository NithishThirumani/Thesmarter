import React, { useCallback, useEffect, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as platformMailService from "../../platformMail/platformMailService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";
import Button from "../../components/Button";
import Input from "../../components/Input";
import Select from "../../components/Select";

const MAILERS = [
  { value: "smtp", label: "SMTP — your SMTP server (e.g. Amazon SES SMTP, Gmail relay)" },
  { value: "ses", label: "Amazon SES — API (HTTPS, IAM credentials)" },
  { value: "sendmail", label: "Sendmail — server binary" },
  { value: "mailgun", label: "Mailgun" },
  { value: "postmark", label: "Postmark" },
  { value: "log", label: "Log — writes to Laravel log (debug)" },
  { value: "array", label: "Array — in-memory only (testing)" },
];

const KNOWN_MAILERS = new Set(MAILERS.map((m) => m.value));

function normalizeMailer(raw) {
  const v = raw != null ? String(raw).trim().toLowerCase() : "";
  if (!v) return "smtp";
  return KNOWN_MAILERS.has(v) ? v : "smtp";
}

const ENCRYPTION = [
  { value: "", label: "None / default" },
  { value: "tls", label: "TLS" },
  { value: "ssl", label: "SSL" },
];

export default function EmailPage() {
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testEmail, setTestEmail] = useState("info@thesmartr.com");
  /** Informational banner when GET reports no persisted row — must not hide the save form */
  const [noPersistedRowInfo, setNoPersistedRowInfo] = useState(null);
  /** After API applies DB overlay — what Laravel actually uses for outbound mail */
  const [runtimeEffective, setRuntimeEffective] = useState(null);

  const [credentialHints, setCredentialHints] = useState({
    smtp_password_set: false,
    aws_access_key_set: false,
    aws_secret_set: false,
    mailgun_secret_set: false,
    postmark_token_set: false,
  });

  const [form, setForm] = useState({
    enabled: true,
    default_mailer: "smtp",
    smtp_host: "",
    smtp_port: "",
    smtp_encryption: "",
    smtp_username: "",
    from_address: "",
    from_name: "",
    aws_default_region: "",
    mailgun_domain: "",
  });

  const [secretsUI, setSecretsUI] = useState({
    smtp_password: "",
    aws_access_key_id: "",
    aws_secret_access_key: "",
    mailgun_secret: "",
    postmark_token: "",
  });

  const [touchedSecrets, setTouchedSecrets] = useState(() => ({
    smtp_password: false,
    aws_access_key_id: false,
    aws_secret_access_key: false,
    mailgun_secret: false,
    postmark_token: false,
  }));

  const load = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await platformMailService.getMailSettings(accessToken);
      const d = res.data || {};
      setRuntimeEffective(d.runtime_effective || null);
      if (!d.persisted_row) {
        setNoPersistedRowInfo({
          message: d.message || "No platform_mail_settings row yet — save below to create it (table must exist; run migrations on the API).",
          env_preview: d.env_preview,
        });
        setCredentialHints({
          smtp_password_set: false,
          aws_access_key_set: false,
          aws_secret_set: false,
          mailgun_secret_set: false,
          postmark_token_set: false,
        });
        setForm((f) => ({
          ...f,
          enabled: d.enabled ?? true,
        }));
      } else {
        setNoPersistedRowInfo(null);
        setCredentialHints({
          smtp_password_set: !!d.smtp_password_set,
          aws_access_key_set: !!(d.secrets && d.secrets.aws_access_key_set),
          aws_secret_set: !!(d.secrets && d.secrets.aws_secret_set),
          mailgun_secret_set: !!(d.secrets && d.secrets.mailgun_secret_set),
          postmark_token_set: !!(d.secrets && d.secrets.postmark_token_set),
        });
        setForm({
          enabled: !!d.enabled,
          default_mailer: normalizeMailer(d.default_mailer),
          smtp_host: d.smtp_host || "",
          smtp_port: d.smtp_port != null ? String(d.smtp_port) : "",
          smtp_encryption: d.smtp_encryption === null ? "" : d.smtp_encryption,
          smtp_username: d.smtp_username || "",
          from_address: d.from_address || "",
          from_name: d.from_name || "",
          aws_default_region: d.aws_default_region || "",
          mailgun_domain: d.mailgun_domain || "",
        });
        setSecretsUI({ smtp_password: "", aws_access_key_id: "", aws_secret_access_key: "", mailgun_secret: "", postmark_token: "" });
        setTouchedSecrets({
          smtp_password: false,
          aws_access_key_id: false,
          aws_secret_access_key: false,
          mailgun_secret: false,
          postmark_token: false,
        });
      }
    } catch (e) {
      addToastSafe("error", "Mail settings", e.message);
    } finally {
      setLoading(false);
    }
  }, [accessToken, addToastSafe]);

  useEffect(() => {
    load();
  }, [load]);

  async function handleSave(e) {
    e?.preventDefault?.();
    if (!accessToken) return;

    const mailer = normalizeMailer(form.default_mailer);

    let portParsed = null;
    if (mailer === "smtp" && form.smtp_port !== "" && form.smtp_port != null) {
      portParsed = parseInt(form.smtp_port, 10);
      if (Number.isNaN(portParsed) || portParsed < 1) {
        addToastSafe("error", "SMTP port", "Enter a valid port number or leave blank.");
        return;
      }
    }

    setSaving(true);
    try {
      const body = {
        enabled: form.enabled,
        default_mailer: normalizeMailer(form.default_mailer) || null,
        smtp_host: form.smtp_host?.trim() || null,
        smtp_port: portParsed,
        smtp_encryption: form.smtp_encryption || "",
        smtp_username: form.smtp_username?.trim() || null,
        from_address: form.from_address?.trim() || null,
        from_name: form.from_name?.trim() || null,
        aws_default_region: form.aws_default_region?.trim() || null,
        mailgun_domain: form.mailgun_domain?.trim() || null,
      };

      if (touchedSecrets.smtp_password) body.smtp_password = secretsUI.smtp_password;
      if (touchedSecrets.aws_access_key_id) body.aws_access_key_id = secretsUI.aws_access_key_id;
      if (touchedSecrets.aws_secret_access_key) body.aws_secret_access_key = secretsUI.aws_secret_access_key;
      if (touchedSecrets.mailgun_secret) body.mailgun_secret = secretsUI.mailgun_secret;
      if (touchedSecrets.postmark_token) body.postmark_token = secretsUI.postmark_token;

      const res = await platformMailService.saveMailSettings(body, accessToken);
      addToastSafe("success", "Saved", res.message || "Mail configuration updated.");
      await load();
    } catch (err) {
      addToastSafe("error", "Save failed", err.message);
    } finally {
      setSaving(false);
    }
  }

  async function handleTestMail() {
    if (!accessToken || !testEmail.trim()) {
      addToastSafe("error", "Test mail", "Enter a recipient email.");
      return;
    }
    setTesting(true);
    try {
      const data = await platformMailService.sendTestMail(testEmail.trim(), accessToken);
      addToastSafe("success", "Test mail", data.message || "Sent successfully (also check spam).");
    } catch (err) {
      addToastSafe("error", "Test mail failed", err.message);
    } finally {
      setTesting(false);
    }
  }

  const selectedMailer = normalizeMailer(form.default_mailer);

  return (
    <div style={{ fontFamily: TYPE.fontBody, color: t.text }}>
      <PageHeader
        title="Email settings"
        subtitle="Secrets are encrypted in the database—values are never sent back for display (only ✓ configured). Overrides merge onto Laravel mail config when the overlay is enabled."
        breadcrumb="Email"
      />

      {!loading && noPersistedRowInfo ? (
        <Card title="Database mail row">
          <p style={{ margin: "0 0 12px", color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.5 }}>
            {noPersistedRowInfo.message}{" "}
            {noPersistedRowInfo.env_preview ? (
              <>
                Current env: mailer <strong>{noPersistedRowInfo.env_preview.mail_default}</strong>, from{" "}
                <strong>{noPersistedRowInfo.env_preview.from_address}</strong>.
              </>
            ) : null}
          </p>
          <p style={{ margin: 0, fontSize: TYPE.xs, color: t.textMuted }}>
            If the table is missing: <code style={{ fontSize: 11 }}>php artisan migrate</code> on the API host. After that,
            click <strong>Save settings</strong> once to persist your AWS/SMTP values.
          </p>
        </Card>
      ) : null}

      {!loading && runtimeEffective ? (
        <Card title="Active mail configuration" style={{ marginTop: noPersistedRowInfo ? 16 : undefined }}>
          <p style={{ margin: "0 0 12px", color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.55 }}>
            This reflects what Laravel will use <strong>right now</strong> after merging database settings (
            <strong>{runtimeEffective.db_overlay_active ? "overlay ON" : "overlay OFF"}</strong>). If overlay is ON but{' '}
            <strong>Default mailer</strong> is blank below, Laravel still follows <code>MAIL_MAILER</code> from{' '}
            <code>.env</code>.
          </p>
          <ul
            style={{
              margin: 0,
              paddingLeft: 20,
              color: t.text,
              fontSize: TYPE.sm,
              lineHeight: 1.6,
              listStylePosition: "outside",
            }}
          >
            <li>
              <strong>Effective mail driver:</strong> <code>{runtimeEffective.active_mailer}</code>
            </li>
            <li>
              <strong>Stored default mailer</strong> (platform row):{' '}
              <code>{runtimeEffective.stored_default_mailer ?? "— blank — falls back to .env"}</code>
            </li>
            <li>
              <strong>From</strong>: {runtimeEffective.from_address_effective || "—"}
              {runtimeEffective.from_name_effective ? <> ({runtimeEffective.from_name_effective})</> : null}
            </li>
            {runtimeEffective.active_mailer === "smtp" && runtimeEffective.smtp ? (
              <li>
                <strong>SMTP:</strong>{' '}
                <code>{runtimeEffective.smtp.host || '?'}:{runtimeEffective.smtp.port || '?'}</code>
                {' · '}encryption{' '}
                <code>{runtimeEffective.smtp.encryption ?? 'none'}</code>
                {' · '}auth{' '}
                <code>
                  {(runtimeEffective.smtp.username_set ? 'username set' : 'no user')},{' '}
                  {runtimeEffective.smtp.password_set ? 'password set' : 'no pwd'}
                </code>
              </li>
            ) : null}
            {runtimeEffective.active_mailer === "ses" && runtimeEffective.ses ? (
              <li>
                <strong>SES region:</strong> <code>{runtimeEffective.ses.region || '(missing)'}</code>
                {' · '}
                <strong>credentials</strong>:{' '}
                <code>{runtimeEffective.ses.credentials_configured ? 'present' : 'missing'}</code>
              </li>
            ) : null}
          </ul>
          {Array.isArray(runtimeEffective.hints) && runtimeEffective.hints.length > 0 ? (
            <div
              style={{
                marginTop: 14,
                padding: '10px 12px',
                borderRadius: 8,
                background: t.bgSubtle,
                border: `1px solid ${t.border}`,
              }}
            >
              <div style={{ fontSize: TYPE.xs, fontWeight: 600, marginBottom: 6, color: t.textMuted }}>Hints</div>
              <ul style={{ margin: 0, paddingLeft: 18, fontSize: TYPE.xs, color: t.textSecondary, lineHeight: 1.55 }}>
                {runtimeEffective.hints.map((h, i) => (
                  <li key={`hint-${i}`}>{h}</li>
                ))}
              </ul>
            </div>
          ) : null}
        </Card>
      ) : null}

      {!loading ? (
        <form onSubmit={handleSave}>
          <Card title="Driver" style={{ marginTop: runtimeEffective || noPersistedRowInfo ? 16 : undefined }}>
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", fontSize: TYPE.sm }}>
                <input
                  type="checkbox"
                  checked={form.enabled}
                  onChange={(e) => setForm((f) => ({ ...f, enabled: e.target.checked }))}
                />
                <span>Use database mail settings&nbsp;</span>
                <span style={{ color: t.textMuted, fontWeight: TYPE.normal }}>
                  When off, the app behaves like plain <code>.env</code>.
                </span>
              </label>
            </div>
            <Select
              label="Default mailer"
              value={selectedMailer}
              onChange={(v) => setForm((f) => ({ ...f, default_mailer: normalizeMailer(v) }))}
              options={MAILERS}
            />
          </Card>

          <Card title="From address (global)" style={{ marginTop: 16 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 16 }}>
              <Input label="From email" type="email" value={form.from_address} onChange={(v) => setForm((f) => ({ ...f, from_address: v }))} />
              <Input label="From name" value={form.from_name} onChange={(v) => setForm((f) => ({ ...f, from_name: v }))} />
            </div>
          </Card>

          {selectedMailer === "smtp" ? (
          <Card title="SMTP server" style={{ marginTop: 16 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 16 }}>
              <Input label="Host" value={form.smtp_host} onChange={(v) => setForm((f) => ({ ...f, smtp_host: v }))} />
              <Input
                label="Port"
                type="number"
                value={form.smtp_port}
                onChange={(v) => setForm((f) => ({ ...f, smtp_port: v }))}
                hint="Common: 587 (TLS), 465 (SSL)."
              />
              <Select
                label="Encryption"
                value={form.smtp_encryption}
                onChange={(v) => setForm((f) => ({ ...f, smtp_encryption: v }))}
                options={ENCRYPTION}
              />
              <Input label="Username" value={form.smtp_username} onChange={(v) => setForm((f) => ({ ...f, smtp_username: v }))} />
              <Input
                label="Password"
                type="password"
                hint={
                  credentialHints.smtp_password_set
                    ? "Stored encrypted—not shown. Leave blank to keep; type a new password to rotate."
                    : "Not set in DB yet."
                }
                value={secretsUI.smtp_password}
                onChange={(v) => {
                  setSecretsUI((s) => ({ ...s, smtp_password: v }));
                  setTouchedSecrets((x) => ({ ...x, smtp_password: true }));
                }}
              />
            </div>
          </Card>
          ) : null}

          {selectedMailer === "ses" ? (
          <Card title="Amazon SES (API)" style={{ marginTop: 16 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 16 }}>
              <Input
                label="Access key"
                hint={
                  credentialHints.aws_access_key_set
                    ? "Stored encrypted—not shown. Leave blank to keep; enter value to rotate."
                    : "Not set in DB yet."
                }
                value={secretsUI.aws_access_key_id}
                onChange={(v) => {
                  setSecretsUI((s) => ({ ...s, aws_access_key_id: v }));
                  setTouchedSecrets((x) => ({ ...x, aws_access_key_id: true }));
                }}
              />
              <Input
                label="Secret key"
                type="password"
                hint={
                  credentialHints.aws_secret_set
                    ? "Stored encrypted—not shown (uses IAM credentials for SES API when mailer = ses)."
                    : "Not set in DB yet."
                }
                value={secretsUI.aws_secret_access_key}
                onChange={(v) => {
                  setSecretsUI((s) => ({ ...s, aws_secret_access_key: v }));
                  setTouchedSecrets((x) => ({ ...x, aws_secret_access_key: true }));
                }}
              />
              <Input
                label="Region"
                value={form.aws_default_region}
                hint="e.g. us-east-1"
                onChange={(v) => setForm((f) => ({ ...f, aws_default_region: v }))}
              />
            </div>
          </Card>
          ) : null}

          {selectedMailer === "mailgun" ? (
          <Card title="Mailgun" style={{ marginTop: 16 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 16 }}>
              <Input label="Mailgun domain" value={form.mailgun_domain} onChange={(v) => setForm((f) => ({ ...f, mailgun_domain: v }))} />
              <Input
                label="Mailgun secret"
                type="password"
                hint={
                  credentialHints.mailgun_secret_set
                    ? "Stored encrypted—not shown. Leave blank to keep; enter to rotate."
                    : "Not set in DB yet."
                }
                value={secretsUI.mailgun_secret}
                onChange={(v) => {
                  setSecretsUI((s) => ({ ...s, mailgun_secret: v }));
                  setTouchedSecrets((x) => ({ ...x, mailgun_secret: true }));
                }}
              />
            </div>
          </Card>
          ) : null}

          {selectedMailer === "postmark" ? (
          <Card title="Postmark" style={{ marginTop: 16 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 16 }}>
              <Input
                label="Postmark token"
                type="password"
                hint={
                  credentialHints.postmark_token_set
                    ? "Stored encrypted—not shown. Leave blank to keep; enter to rotate."
                    : "Not set in DB yet."
                }
                value={secretsUI.postmark_token}
                onChange={(v) => {
                  setSecretsUI((s) => ({ ...s, postmark_token: v }));
                  setTouchedSecrets((x) => ({ ...x, postmark_token: true }));
                }}
              />
            </div>
          </Card>
          ) : null}

          {(selectedMailer === "log" || selectedMailer === "array") ? (
          <Card title={selectedMailer === "log" ? "Log mailer" : "Array mailer"} style={{ marginTop: 16 }}>
            <p style={{ margin: 0, fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.6 }}>
              {selectedMailer === "log"
                ? "Messages are written to the Laravel log — useful for debugging. Set From above; no SMTP or API keys required here."
                : "Messages are captured in memory only for automated tests — not delivered. Set From above."}
            </p>
          </Card>
          ) : null}

          {selectedMailer === "sendmail" ? (
          <Card title="Sendmail" style={{ marginTop: 16 }}>
            <p style={{ margin: 0, fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.6 }}>
              Uses the server sendmail binary / path from Laravel configuration. Configure <code>config/mail.php</code> on the API host if needed; set
              From above.
            </p>
          </Card>
          ) : null}

          <div style={{ display: "flex", flexWrap: "wrap", gap: 12, marginTop: 20, alignItems: "flex-end" }}>
            <Button variant="primary" type="submit" disabled={saving || loading}>
              {saving ? "Saving…" : "Save settings"}
            </Button>
          </div>

          <Card title="Send test mail" style={{ marginTop: 16 }}>
            <p style={{ margin: "0 0 14px", fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.6 }}>
              Uses the credentials saved in <strong>platform_mail_settings</strong> after &quot;Use database mail settings&quot; is enabled.
              {selectedMailer === "ses" ? (
                <>
                  {" "}
                  For <strong>Amazon SES (API)</strong>, identities must be verified in SES — typical Bizwy domains are{" "}
                  <strong>bizwy.in</strong> and <strong>bizwy.com</strong>. In SES <strong>sandbox</strong>, recipients must be verified too unless the
                  account is in production.
                </>
              ) : selectedMailer === "smtp" ? (
                <>
                  {" "}
                  With <strong>SMTP</strong>, use host/port/TLS from your provider (e.g. SES SMTP credentials are separate from IAM API keys).
                </>
              ) : (
                <> Save settings, then send a test to confirm delivery.</>
              )}
            </p>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 12, alignItems: "flex-end" }}>
              <div style={{ flex: "1 1 260px", minWidth: 200 }}>
                <Input label="Recipient email" type="email" value={testEmail} onChange={(v) => setTestEmail(v)} />
              </div>
              <Button variant="secondary" type="button" disabled={testing || loading} onClick={handleTestMail}>
                {testing ? "Sending…" : "Send test"}
              </Button>
            </div>
          </Card>
        </form>
      ) : null}

      {loading ? (
        <Card title="Mail settings">
          <p style={{ color: t.textMuted }}>Loading…</p>
        </Card>
      ) : null}

      <p style={{ marginTop: 20, fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.55, maxWidth: 760 }}>
        After changing passwords or keys from this screen, optionally restart queued workers so long-running PHP processes reload config. Clearing a
        Laravel <code style={{ fontSize: 11 }}>config</code> cache may be necessary if your deploy uses{" "}
        <code style={{ fontSize: 11 }}>config:cache</code>.
      </p>
    </div>
  );
}
