import React, { useEffect, useState } from "react";
import { useNavigate, useOutletContext, useParams } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as companyService from "../../company/companyService";
import * as superUserService from "../../users/superUserService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Loader from "../../components/Loader";
import Input from "../../components/Input";
import { SuperUserAvatarPicker, SuperUserProfileFields } from "../users/SuperUserFormShared";

const EMAIL_OK = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const PRIV_KEYS = ["Access_priv", "Read_priv", "Create_priv", "Update_priv", "Delete_priv"];

export default function EditSuperUserPage() {
  const { companyId, userId } = useParams();
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [companyName, setCompanyName] = useState("");
  const [saving, setSaving] = useState(false);
  const [reactivateBusy, setReactivateBusy] = useState(false);
  const [existingAvatarUrl, setExistingAvatarUrl] = useState(null);
  const [detailRow, setDetailRow] = useState(null);
  const [execModules, setExecModules] = useState([]);
  const [modulesLoading, setModulesLoading] = useState(false);
  const [modulesError, setModulesError] = useState("");
  /** @type {Record<number, Record<string, string>>} */
  const [permByModule, setPermByModule] = useState({});
  const [isActive, setIsActive] = useState(true);

  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    mobile: "",
    email: "",
    gender: "",
    date_of_birth: "",
    marital_status: "",
  });
  const [fieldErrors, setFieldErrors] = useState({});
  const [avatarFile, setAvatarFile] = useState(null);
  const [removeAvatar, setRemoveAvatar] = useState(false);

  useEffect(() => {
    if (!companyId || !userId || !accessToken) {
      setLoading(false);
      return;
    }
    let cancelled = false;
    (async () => {
      setLoading(true);
      setModulesLoading(true);
      setModulesError("");
      try {
        const [compRes, userRes, modRes] = await Promise.all([
          companyService.getCompany(companyId, accessToken),
          superUserService.getSuperUser(companyId, userId, accessToken),
          superUserService.listExecutiveModules(companyId, accessToken).catch((e) => {
            if (!cancelled) setModulesError(e.message || "Could not load modules.");
            return { data: [] };
          }),
        ]);
        if (cancelled) return;
        setCompanyName(compRes?.data?.company_name || "");
        const row = userRes?.data;
        if (!row) throw new Error("Not found");
        setDetailRow(row);
        setExistingAvatarUrl(row.avatar_url || null);
        setIsActive(Number(row.status) === 1);
        setForm({
          first_name: row.first_name || "",
          last_name: row.last_name || "",
          mobile: row.mobile || "",
          email: row.email || "",
          gender: row.gender || "",
          date_of_birth: row.date_of_birth ? String(row.date_of_birth).slice(0, 10) : "",
          marital_status: row.marital_status || "",
        });
        setExecModules(Array.isArray(modRes?.data) ? modRes.data : []);
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Load failed", e.message);
      } finally {
        if (!cancelled) {
          setLoading(false);
          setModulesLoading(false);
        }
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [companyId, userId, accessToken, addToastSafe]);

  useEffect(() => {
    if (!detailRow || !execModules.length) return;
    const snap = detailRow.modules_permissions || [];
    setPermByModule(() => {
      const next = {};
      execModules.forEach((m) => {
        const mid = Number(m.module_id);
        if (Number.isNaN(mid) || mid <= 0) return;
        const row = snap.find((x) => Number(x.module_id) === mid);
        const d = m.defaults || {};
        next[mid] = row
          ? {
              Access_priv: row.Access_priv ?? d.Access_priv ?? "Y",
              Read_priv: row.Read_priv ?? d.Read_priv ?? "Y",
              Create_priv: row.Create_priv ?? d.Create_priv ?? "N",
              Update_priv: row.Update_priv ?? d.Update_priv ?? "N",
              Delete_priv: row.Delete_priv ?? d.Delete_priv ?? "N",
            }
          : {
              Access_priv: d.Access_priv ?? "Y",
              Read_priv: d.Read_priv ?? "Y",
              Create_priv: d.Create_priv ?? "N",
              Update_priv: d.Update_priv ?? "N",
              Delete_priv: d.Delete_priv ?? "N",
            };
      });
      return next;
    });
  }, [detailRow, execModules]);

  const accentLine = `linear-gradient(90deg, ${t.accent} 0%, ${t.accent}22 40%, transparent 100%)`;

  const validate = () => {
    const e = {};
    if (!String(form.first_name || "").trim()) e.first_name = "First name is required.";
    if (!String(form.email || "").trim()) e.email = "Email is required.";
    else if (!EMAIL_OK.test(String(form.email).trim())) e.email = "Enter a valid email address.";
    setFieldErrors(e);
    return Object.keys(e).length === 0;
  };

  const buildPermissionsPayload = () =>
    execModules.map((m) => {
      const mid = Number(m.module_id);
      const row = permByModule[mid] || {};
      const d = m.defaults || {};
      return {
        module_id: mid,
        Access_priv: row.Access_priv ?? d.Access_priv ?? "Y",
        Read_priv: row.Read_priv ?? d.Read_priv ?? "Y",
        Create_priv: row.Create_priv ?? d.Create_priv ?? "N",
        Update_priv: row.Update_priv ?? d.Update_priv ?? "N",
        Delete_priv: row.Delete_priv ?? d.Delete_priv ?? "N",
      };
    });

  const togglePriv = (moduleId, key) => {
    setPermByModule((prev) => {
      const cur = prev[moduleId] || {};
      const nextY = (cur[key] || "N") !== "Y";
      return {
        ...prev,
        [moduleId]: {
          ...cur,
          [key]: nextY ? "Y" : "N",
        },
      };
    });
  };

  const handleReactivate = async () => {
    if (!companyId || !userId || !accessToken) return;
    setReactivateBusy(true);
    try {
      await superUserService.reactivateExecutive(companyId, userId, accessToken);
      setIsActive(true);
      setDetailRow((d) => (d ? { ...d, status: 1 } : d));
      addToastSafe("success", "Reactivated", "Executive access restored for this company.");
    } catch (e) {
      addToastSafe("error", "Reactivate failed", e.message);
    } finally {
      setReactivateBusy(false);
    }
  };

  const handleSave = async (ev) => {
    ev.preventDefault();
    if (!validate() || !companyId || !userId || !accessToken) return;
    setSaving(true);
    try {
      const basePayload = {
        first_name: String(form.first_name).trim(),
        last_name: String(form.last_name || "").trim(),
        email: String(form.email || "").trim().toLowerCase(),
        gender: form.gender || null,
        date_of_birth: form.date_of_birth || null,
        marital_status: form.marital_status || null,
        permissions: buildPermissionsPayload(),
        is_active: isActive,
      };

      const useMultipart = Boolean(avatarFile) || removeAvatar;
      if (useMultipart) {
        const fd = new FormData();
        fd.append("first_name", basePayload.first_name);
        fd.append("last_name", basePayload.last_name || "");
        fd.append("email", basePayload.email);
        if (basePayload.gender) fd.append("gender", basePayload.gender);
        if (basePayload.date_of_birth) fd.append("date_of_birth", basePayload.date_of_birth);
        if (basePayload.marital_status) fd.append("marital_status", basePayload.marital_status);
        fd.append("permissions", JSON.stringify(basePayload.permissions));
        fd.append("is_active", basePayload.is_active ? "1" : "0");
        if (removeAvatar && !avatarFile) fd.append("remove_avatar", "1");
        if (avatarFile) fd.append("avatar", avatarFile);
        await superUserService.updateSuperUser(companyId, userId, fd, accessToken);
      } else {
        await superUserService.updateSuperUser(companyId, userId, basePayload, accessToken);
      }
      addToastSafe("success", "Saved", "Super user updated.");
      navigate("/superusers");
    } catch (e) {
      addToastSafe("error", "Update failed", e.message);
    } finally {
      setSaving(false);
    }
  };

  const onAvatarClearSelection = () => {
    setAvatarFile(null);
    setRemoveAvatar(true);
    setExistingAvatarUrl(null);
  };

  const onPickAvatar = (file) => {
    setAvatarFile(file);
    if (file) setRemoveAvatar(false);
  };

  if (loading) {
    return (
      <div style={{ background: "transparent", minHeight: "100%", paddingBottom: 24 }}>
        <PageHeader title="Loading…" breadcrumb="Management" />
        <div style={{ padding: 48, display: "flex", justifyContent: "center" }}>
          <Loader />
        </div>
      </div>
    );
  }

  const inactive = !isActive;

  return (
    <div style={{ background: "transparent", minHeight: "100%", paddingBottom: 40 }}>
      <PageHeader
        breadcrumb="Management"
        title="Edit super user"
        subtitle={companyName ? `${companyName}` : undefined}
        action={
          <div style={{ display: "flex", gap: 8 }}>
            <Button variant="ghost" onClick={() => navigate("/superusers")}>
              Cancel
            </Button>
          </div>
        }
      />

      <div style={{ padding: "0 20px", maxWidth: 920 }}>
        {inactive ? (
          <div
            style={{
              marginBottom: 16,
              padding: "12px 14px",
              borderRadius: 8,
              border: `1px solid ${t.border}`,
              background: t.bgElevated,
              color: t.textSecondary,
              fontSize: TYPE.sm,
              lineHeight: 1.55,
              display: "flex",
              flexWrap: "wrap",
              gap: 12,
              alignItems: "center",
              justifyContent: "space-between",
            }}
          >
            <span>This executive is inactive for this company. You can edit details or restore access.</span>
            <Button type="button" size="sm" variant="primary" disabled={reactivateBusy || saving} onClick={handleReactivate}>
              {reactivateBusy ? "Working…" : "Reactivate"}
            </Button>
          </div>
        ) : null}

        <form onSubmit={handleSave}>
          <div
            style={{
              borderRadius: 14,
              border: `1px solid ${t.border}`,
              background: t.bgCard,
              boxShadow: "0 18px 48px rgba(0,0,0,0.35)",
              overflow: "hidden",
            }}
          >
            <div style={{ height: 3, background: accentLine }} aria-hidden />
            <div style={{ padding: "22px 22px 28px" }}>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(260px, 1fr))", gap: 16, marginBottom: 18 }}>
                <Input
                  label="Company"
                  value={`${companyName || detailRow?.company_name || ""}${detailRow?.company_code ? ` · ${detailRow.company_code}` : ""}`}
                  onChange={() => {}}
                  disabled
                  hint="Company cannot be changed."
                />
              </div>
              <label
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 10,
                  marginBottom: 18,
                  cursor: saving ? "not-allowed" : "pointer",
                  fontSize: TYPE.sm,
                  color: t.text,
                  fontFamily: TYPE.fontBody,
                }}
              >
                <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} disabled={saving} />
                Active at this company
              </label>

              <SuperUserAvatarPicker
                existingImageUrl={removeAvatar && !avatarFile ? null : existingAvatarUrl}
                file={avatarFile}
                onFileChange={onPickAvatar}
                onClearFile={onAvatarClearSelection}
                firstName={form.first_name}
                lastName={form.last_name}
                disabled={saving}
              />
              {existingAvatarUrl && !avatarFile && !removeAvatar && (
                <button
                  type="button"
                  disabled={saving}
                  onClick={() => {
                    setRemoveAvatar(true);
                    setExistingAvatarUrl(null);
                  }}
                  style={{
                    marginTop: 6,
                    marginBottom: 12,
                    background: "none",
                    border: "none",
                    padding: 0,
                    color: "#ef4444",
                    cursor: saving ? "not-allowed" : "pointer",
                    fontSize: TYPE.sm,
                    fontFamily: TYPE.fontBody,
                    textDecoration: "underline",
                  }}
                >
                  Remove saved profile photo
                </button>
              )}

              <SuperUserProfileFields form={form} setForm={setForm} fieldErrors={fieldErrors} disabled={saving} mobileReadOnly />

              <div style={{ marginTop: 28 }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
                  <span
                    style={{
                      fontSize: 11,
                      fontWeight: TYPE.bold,
                      color: t.accent,
                      letterSpacing: "0.12em",
                      textTransform: "uppercase",
                      fontFamily: TYPE.fontBody,
                    }}
                  >
                    Module permissions
                  </span>
                  <span style={{ height: 1, flex: 1, background: `linear-gradient(90deg, ${t.border} 0%, transparent 100%)` }} />
                </div>
                <p style={{ margin: "0 0 12px", fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.55, maxWidth: 680 }}>
                  Changes apply to this company&apos;s executive modules. Defaults apply if the module list could not be loaded.
                </p>
                {modulesLoading && execModules.length === 0 ? (
                  <div style={{ padding: "16px 0", display: "flex", justifyContent: "center" }}>
                    <Loader />
                  </div>
                ) : null}
                {modulesError ? (
                  <div
                    style={{
                      padding: "10px 12px",
                      borderRadius: 8,
                      border: `1px solid ${t.border}`,
                      background: t.bgElevated,
                      color: t.textSecondary,
                      fontSize: TYPE.sm,
                      marginBottom: 12,
                    }}
                  >
                    {modulesError}
                  </div>
                ) : null}
                {execModules.length > 0 ? (
                  <div
                    style={{
                      maxHeight: 280,
                      overflow: "auto",
                      border: `1px solid ${t.border}`,
                      borderRadius: 8,
                      background: t.bgElevated,
                    }}
                  >
                    <table style={{ width: "100%", borderCollapse: "collapse", fontSize: TYPE.xs, fontFamily: TYPE.fontBody }}>
                      <thead>
                        <tr style={{ background: t.bg }}>
                          <th style={{ textAlign: "left", padding: "10px 12px", color: t.textMuted, fontWeight: TYPE.bold }}>Module</th>
                          {PRIV_KEYS.map((k) => (
                            <th key={k} style={{ textAlign: "center", padding: "10px 6px", color: t.textMuted, fontWeight: TYPE.bold, whiteSpace: "nowrap" }}>
                              {k.replace("_priv", "").slice(0, 3)}
                            </th>
                          ))}
                        </tr>
                      </thead>
                      <tbody>
                        {execModules.map((m) => {
                          const mid = Number(m.module_id);
                          const row = permByModule[mid] || {};
                          const d = m.defaults || {};
                          const val = (key) => (row[key] ?? d[key] ?? "N") === "Y";
                          return (
                            <tr key={mid} style={{ borderTop: `1px solid ${t.border}` }}>
                              <td style={{ padding: "8px 12px", color: t.text, fontWeight: TYPE.medium }}>{m.module_name || `Module ${mid}`}</td>
                              {PRIV_KEYS.map((key) => (
                                <td key={key} style={{ textAlign: "center", padding: "6px" }}>
                                  <input
                                    type="checkbox"
                                    checked={val(key)}
                                    onChange={() => togglePriv(mid, key)}
                                    disabled={saving}
                                    aria-label={`${m.module_name} ${key}`}
                                  />
                                </td>
                              ))}
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                ) : null}
              </div>

              <div style={{ marginTop: 24, display: "flex", gap: 10, flexWrap: "wrap" }}>
                <Button type="submit" variant="primary" disabled={saving}>
                  {saving ? "Saving…" : "Save changes"}
                </Button>
                <Button type="button" variant="ghost" disabled={saving} onClick={() => navigate("/superusers")}>
                  Back to super users
                </Button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}
