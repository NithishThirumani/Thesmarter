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
import Modal from "../../components/Modal";
import Select from "../../components/Select";
import { SuperUserAvatarPicker, SuperUserProfileFields } from "../users/SuperUserFormShared";

const EMAIL_OK = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const PRIV_KEYS = ["Access_priv", "Read_priv", "Create_priv", "Update_priv", "Delete_priv"];

function branchIsActive(b) {
  const s = b?.branch_status;
  return s === "A" || s === "a" || s === 1 || s === "1";
}

const initialForm = () => ({
  first_name: "",
  last_name: "",
  mobile: "",
  email: "",
  gender: "",
  date_of_birth: "",
  marital_status: "",
});

export default function CreateSuperUserPage() {
  const { companyId: companyIdParam } = useParams();
  const routeCompanyId = companyIdParam ? String(companyIdParam).trim() : "";
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [pickedCompanyId, setPickedCompanyId] = useState("");
  const effectiveCompanyId = routeCompanyId || pickedCompanyId;

  const [companyBranches, setCompanyBranches] = useState([]);
  /** @type {number | null} */
  const [selectedBranchId, setSelectedBranchId] = useState(null);
  const [companiesRows, setCompaniesRows] = useState([]);
  const [loadingCompaniesList, setLoadingCompaniesList] = useState(false);

  const [loadingCompany, setLoadingCompany] = useState(Boolean(routeCompanyId));
  const [companyName, setCompanyName] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState(initialForm);
  const [fieldErrors, setFieldErrors] = useState({});
  const [avatarFile, setAvatarFile] = useState(null);

  const [execModules, setExecModules] = useState([]);
  const [modulesLoading, setModulesLoading] = useState(false);
  const [modulesError, setModulesError] = useState("");
  /** @type {Record<number, Record<string, string>>} */
  const [permByModule, setPermByModule] = useState({});
  const [pendingConflict, setPendingConflict] = useState(null);

  const [mobileCheck, setMobileCheck] = useState(null);
  const [checkingMobile, setCheckingMobile] = useState(false);

  useEffect(() => {
    if (routeCompanyId || !accessToken) return;
    let cancelled = false;
    (async () => {
      setLoadingCompaniesList(true);
      try {
        const res = await companyService.listCompanies({ page: 1, per_page: 200 }, accessToken);
        if (!cancelled) setCompaniesRows(Array.isArray(res?.data) ? res.data : []);
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Companies", e.message);
      } finally {
        if (!cancelled) setLoadingCompaniesList(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [routeCompanyId, accessToken, addToastSafe]);

  useEffect(() => {
    if (!effectiveCompanyId || !accessToken) {
      setLoadingCompany(false);
      setCompanyName("");
      setCompanyBranches([]);
      setSelectedBranchId(null);
      return;
    }
    let cancelled = false;
    (async () => {
      setLoadingCompany(true);
      try {
        const res = await companyService.getCompany(effectiveCompanyId, accessToken);
        if (cancelled) return;
        const d = res?.data || {};
        setCompanyName(String(d.company_name || ""));
        const raw = Array.isArray(d.branches) ? d.branches : [];
        setCompanyBranches(raw.filter(branchIsActive));
        setSelectedBranchId(null);
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Error", e.message);
      } finally {
        if (!cancelled) setLoadingCompany(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [effectiveCompanyId, accessToken, addToastSafe]);

  useEffect(() => {
    setMobileCheck(null);
  }, [effectiveCompanyId, selectedBranchId]);

  const accentLine = `linear-gradient(90deg, ${t.accent} 0%, ${t.accent}22 40%, transparent 100%)`;

  const branchGatePassed = Boolean(
    effectiveCompanyId && selectedBranchId != null && !Number.isNaN(Number(selectedBranchId)) && Number(selectedBranchId) > 0 && !loadingCompany
  );
  const companyOptions = [
    { value: "", label: loadingCompaniesList ? "Loading companies…" : "Select a company…" },
    ...companiesRows.map((c) => ({
      value: String(c.company_id),
      label: `${c.company_name || "Company"}${c.company_code ? ` · ${c.company_code}` : ""}`,
    })),
  ];

  const goCancel = () => {
    if (routeCompanyId) navigate(`/company/${routeCompanyId}`);
    else navigate("/superusers");
  };

  const runMobileCheck = async () => {
    if (!effectiveCompanyId || !accessToken) return;
    const raw = String(form.mobile || "").trim();
    if (String(raw.replace(/\D/g, "")).length < 10) {
      addToastSafe("info", "Mobile number", "Enter at least 10 digits before checking.");
      return;
    }
    setCheckingMobile(true);
    setMobileCheck(null);
    try {
      const res = await superUserService.checkSuperUserMobile(effectiveCompanyId, raw, accessToken);
      setMobileCheck(res);
      if (res?.data?.normalized) {
        setForm((f) => ({ ...f, mobile: res.data.normalized }));
      }
      if (res?.data?.already_super_for_company) {
        addToastSafe("error", "Not available", res.data.message || "This number is already a super user here.");
      } else {
        addToastSafe("success", "Number checked", res.data?.message || "You can continue.");
      }
    } catch (e) {
      addToastSafe("error", "Check failed", e.message);
    } finally {
      setCheckingMobile(false);
    }
  };

  useEffect(() => {
    setMobileCheck(null);
  }, [form.mobile]);

  const blockedByDuplicate = mobileCheck?.data?.already_super_for_company === true;
  const canShowProfile =
    branchGatePassed &&
    mobileCheck?.success &&
    mobileCheck?.data?.valid_format === true &&
    !mobileCheck?.data?.already_super_for_company;

  useEffect(() => {
    if (!canShowProfile || !effectiveCompanyId || !accessToken) {
      setExecModules([]);
      setModulesError("");
      return;
    }
    let cancelled = false;
    (async () => {
      setModulesLoading(true);
      setModulesError("");
      try {
        const res = await superUserService.listExecutiveModules(effectiveCompanyId, accessToken);
        if (!cancelled) setExecModules(Array.isArray(res?.data) ? res.data : []);
      } catch (e) {
        if (!cancelled) setModulesError(e.message || "Could not load modules.");
      } finally {
        if (!cancelled) setModulesLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [canShowProfile, effectiveCompanyId, accessToken]);

  useEffect(() => {
    if (!execModules.length) return;
    setPermByModule((prev) => {
      const next = { ...prev };
      execModules.forEach((m) => {
        const mid = Number(m.module_id);
        if (Number.isNaN(mid) || mid <= 0) return;
        if (next[mid]) return;
        const d = m.defaults || {};
        next[mid] = {
          Access_priv: d.Access_priv ?? "Y",
          Read_priv: d.Read_priv ?? "Y",
          Create_priv: d.Create_priv ?? "N",
          Update_priv: d.Update_priv ?? "N",
          Delete_priv: d.Delete_priv ?? "N",
        };
      });
      return next;
    });
  }, [execModules]);

  const validate = () => {
    const e = {};
    if (!String(form.first_name || "").trim()) e.first_name = "First name is required.";
    if (!String(form.mobile || "").trim()) e.mobile = "Mobile is required.";
    else if (String(form.mobile).replace(/\D/g, "").length < 10) e.mobile = "Enter a valid mobile number (at least 10 digits).";
    if (!String(form.email || "").trim()) e.email = "Email is required.";
    else if (!EMAIL_OK.test(String(form.email).trim())) e.email = "Enter a valid email address.";
    setFieldErrors(e);
    return Object.keys(e).length === 0;
  };

  const buildPayload = () => {
    const o = {
      first_name: String(form.first_name).trim(),
      last_name: String(form.last_name || "").trim() || undefined,
      mobile: String(form.mobile || "").trim(),
      email: String(form.email || "").trim().toLowerCase(),
    };
    if (form.gender) o.gender = form.gender;
    if (form.date_of_birth) o.date_of_birth = form.date_of_birth;
    if (form.marital_status) o.marital_status = form.marital_status;
    return o;
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

  const runCreate = async (confirmExtra = {}) => {
    if (!branchGatePassed) {
      addToastSafe("info", "Company & branches", "Choose a company and one active branch first.");
      return;
    }
    if (!validate() || !effectiveCompanyId || !accessToken || blockedByDuplicate) return;
    setSubmitting(true);
    try {
      const payload = {
        ...buildPayload(),
        branch_ids: selectedBranchId != null ? [Number(selectedBranchId)] : [],
        permissions: buildPermissionsPayload(),
        ...confirmExtra,
      };
      const opts = avatarFile ? { avatarFile } : {};
      const res = await superUserService.createSuperUser(effectiveCompanyId, payload, accessToken, opts);
      addToastSafe("success", "Done", res.message || "Executive created.");
      setPendingConflict(null);
      navigate(`/company/${effectiveCompanyId}`, { replace: true });
    } catch (err) {
      if (err.conflict === "OWNER_REQUIRES_CONFIRM" || err.conflict === "CUSTOMER_REQUIRES_PROMOTE") {
        setPendingConflict({ code: err.conflict, message: err.message || "" });
      } else {
        addToastSafe("error", "Request failed", err.message || "Something went wrong.");
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmit = async (ev) => {
    ev.preventDefault();
    if (!branchGatePassed) {
      addToastSafe("info", "Company & branches", "Choose a company and one active branch before continuing.");
      return;
    }
    if (!canShowProfile) {
      addToastSafe("info", "Verify mobile", 'Use "Check number" and wait for a green light before submitting.');
      return;
    }
    await runCreate({});
  };

  if (routeCompanyId && loadingCompany) {
    return (
      <div style={{ background: "transparent", minHeight: "100%", paddingBottom: 24 }}>
        <PageHeader title="Loading…" breadcrumb="Companies" />
        <div style={{ padding: 48, display: "flex", justifyContent: "center" }}>
          <Loader />
        </div>
      </div>
    );
  }

  return (
    <div style={{ background: "transparent", minHeight: "100%", paddingBottom: 40 }}>
      <PageHeader
        breadcrumb="Companies"
        title="Create super user"
        subtitle={companyName ? `Company: ${companyName}` : undefined}
        action={
          <Button variant="ghost" onClick={goCancel}>
            Cancel
          </Button>
        }
      />

      <div style={{ padding: "0 20px", maxWidth: 920 }}>
        <p style={{ color: t.textSecondary, fontSize: TYPE.sm, margin: "0 0 20px", lineHeight: 1.65, maxWidth: 720 }}>
          Pick the company and a single active branch, verify the mobile number, then complete profile details. New accounts receive a generated PIN by email.
        </p>

        <form onSubmit={handleSubmit}>
          <div
            style={{
              borderRadius: 14,
              border: `1px solid ${t.border}`,
              background: t.bgCard,
              boxShadow: "0 18px 48px rgba(0,0,0,0.35)",
              overflow: "hidden",
              marginBottom: 18,
            }}
          >
            <div style={{ height: 3, background: accentLine }} aria-hidden />
            <div style={{ padding: "20px 22px" }}>
              <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 16 }}>
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
                  Step 1 — Company & branches
                </span>
                <span style={{ height: 1, flex: 1, background: `linear-gradient(90deg, ${t.border} 0%, transparent 100%)` }} />
              </div>
              {!routeCompanyId ? (
                <div style={{ maxWidth: 420, marginBottom: 16 }}>
                  <Select
                    label="Company *"
                    value={pickedCompanyId}
                    onChange={(v) => setPickedCompanyId(v)}
                    options={companyOptions}
                    disabled={loadingCompaniesList || submitting}
                  />
                </div>
              ) : (
                <div style={{ marginBottom: 14, fontSize: TYPE.sm, color: t.textSecondary, fontFamily: TYPE.fontBody }}>
                  <strong style={{ color: t.text }}>Company:</strong> {companyName || "…"}
                </div>
              )}
              {effectiveCompanyId && loadingCompany ? (
                <div style={{ padding: "12px 0", display: "flex", justifyContent: "center" }}>
                  <Loader />
                </div>
              ) : null}
              {effectiveCompanyId && !loadingCompany && companyBranches.length === 0 ? (
                <div
                  style={{
                    padding: "12px 14px",
                    borderRadius: 8,
                    border: `1px solid ${t.border}`,
                    background: t.bgElevated,
                    color: t.textSecondary,
                    fontSize: TYPE.sm,
                  }}
                >
                  No active branches found for this company. Add or activate a branch in company settings first.
                </div>
              ) : null}
              {effectiveCompanyId && !loadingCompany && companyBranches.length > 0 ? (
                <div style={{ display: "grid", gap: 10 }}>
                  <span style={{ fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontBody }}>
                    Select exactly one branch this executive will access *
                  </span>
                  {companyBranches.map((b) => {
                    const bid = Number(b.branch_id);
                    return (
                      <label
                        key={bid}
                        style={{
                          display: "flex",
                          alignItems: "center",
                          gap: 10,
                          cursor: submitting ? "not-allowed" : "pointer",
                          fontSize: TYPE.sm,
                          color: t.text,
                          fontFamily: TYPE.fontBody,
                        }}
                      >
                        <input
                          type="radio"
                          name="create-super-user-branch"
                          checked={selectedBranchId === bid}
                          disabled={submitting}
                          onChange={() => setSelectedBranchId(bid)}
                        />
                        <span>
                          {b.branch_name || `Branch #${bid}`}
                          <span style={{ color: t.textMuted, marginLeft: 8 }}>#{bid}</span>
                          {b.branch_type ? <span style={{ color: t.textMuted, marginLeft: 8 }}>({b.branch_type})</span> : null}
                        </span>
                      </label>
                    );
                  })}
                </div>
              ) : null}
            </div>
          </div>

          {branchGatePassed ? (
            <div
              style={{
                borderRadius: 14,
                border: `1px solid ${t.border}`,
                background: t.bgCard,
                boxShadow: "0 18px 48px rgba(0,0,0,0.35)",
                overflow: "hidden",
                marginBottom: 18,
              }}
            >
              <div style={{ height: 3, background: accentLine }} aria-hidden />
              <div style={{ padding: "20px 22px" }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 16 }}>
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
                    Step 2 — Mobile
                  </span>
                  <span style={{ height: 1, flex: 1, background: `linear-gradient(90deg, ${t.border} 0%, transparent 100%)` }} />
                </div>
                <div style={{ display: "flex", flexWrap: "wrap", gap: 12, alignItems: "flex-end" }}>
                  <div style={{ flex: "1 1 240px", minWidth: 200 }}>
                    <label
                      style={{
                        fontSize: TYPE.xs,
                        fontWeight: TYPE.semibold,
                        color: t.textSecondary,
                        letterSpacing: "0.06em",
                        textTransform: "uppercase",
                        display: "block",
                        marginBottom: 6,
                      }}
                    >
                      Mobile *
                    </label>
                    <input
                      value={form.mobile}
                      onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))}
                      inputMode="tel"
                      placeholder="Digits only or formatted"
                      disabled={submitting}
                      style={{
                        width: "100%",
                        height: 38,
                        padding: "0 12px",
                        background: t.bgElevated,
                        border: `1px solid ${t.border}`,
                        borderRadius: 6,
                        color: t.text,
                        fontSize: TYPE.base,
                        fontFamily: TYPE.fontBody,
                        boxSizing: "border-box",
                      }}
                    />
                  </div>
                  <Button type="button" variant="secondary" disabled={checkingMobile || submitting} onClick={runMobileCheck}>
                    {checkingMobile ? "Checking…" : "Check number"}
                  </Button>
                </div>

                {mobileCheck?.data && (
                  <div
                    style={{
                      marginTop: 14,
                      padding: "12px 14px",
                      borderRadius: 8,
                      border: `1px solid ${blockedByDuplicate ? t.danger + "55" : t.success + "44"}`,
                      background: blockedByDuplicate ? t.dangerBg : t.successBg,
                      color: blockedByDuplicate ? t.danger : t.success,
                      fontSize: TYPE.sm,
                      lineHeight: 1.5,
                      fontFamily: TYPE.fontBody,
                    }}
                  >
                    {mobileCheck.data.message}
                  </div>
                )}
              </div>
            </div>
          ) : null}

          {canShowProfile && (
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
              <div style={{ padding: "22px 22px 26px" }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 18 }}>
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
                    Step 3 — Profile
                  </span>
                  <span style={{ height: 1, flex: 1, background: `linear-gradient(90deg, ${t.border} 0%, transparent 100%)` }} />
                </div>

                <SuperUserAvatarPicker
                  existingImageUrl={null}
                  file={avatarFile}
                  onFileChange={setAvatarFile}
                  onClearFile={() => setAvatarFile(null)}
                  firstName={form.first_name}
                  lastName={form.last_name}
                  disabled={submitting}
                />

                <SuperUserProfileFields form={form} setForm={setForm} fieldErrors={fieldErrors} disabled={submitting} />

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
                    Toggle Access / Read / Create / Update / Delete per enabled module for this company. If loading fails, defaults still apply on save.
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
                        border: `1px solid ${t.warning ? t.warning + "44" : t.border}`,
                        background: t.warningBg || t.bgElevated,
                        color: t.warning || t.textSecondary,
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
                                      disabled={submitting}
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
                  <Button type="submit" variant="primary" disabled={submitting || blockedByDuplicate}>
                    {submitting ? "Creating…" : "Create super user"}
                  </Button>
                  <Button type="button" variant="ghost" disabled={submitting} onClick={goCancel}>
                    Back
                  </Button>
                </div>
              </div>
            </div>
          )}
        </form>
      </div>

      <Modal
        open={!!pendingConflict}
        onClose={() => !submitting && setPendingConflict(null)}
        title={
          pendingConflict?.code === "OWNER_REQUIRES_CONFIRM"
            ? "Convert owner to executive?"
            : pendingConflict?.code === "CUSTOMER_REQUIRES_PROMOTE"
              ? "Promote customer to executive?"
              : "Confirm"
        }
        width={480}
      >
        {pendingConflict && (
          <>
            <p style={{ margin: "0 0 20px", fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.6, fontFamily: TYPE.fontBody }}>
              {pendingConflict.message}
            </p>
            <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", flexWrap: "wrap" }}>
              <Button variant="ghost" disabled={submitting} onClick={() => setPendingConflict(null)}>
                Cancel
              </Button>
              <Button
                variant="primary"
                disabled={submitting}
                onClick={() => {
                  if (pendingConflict.code === "OWNER_REQUIRES_CONFIRM") runCreate({ confirm_convert_owner: true });
                  else if (pendingConflict.code === "CUSTOMER_REQUIRES_PROMOTE") runCreate({ confirm_promote_customer: true });
                }}
              >
                {submitting ? "Saving…" : "Confirm and continue"}
              </Button>
            </div>
          </>
        )}
      </Modal>
    </div>
  );
}
