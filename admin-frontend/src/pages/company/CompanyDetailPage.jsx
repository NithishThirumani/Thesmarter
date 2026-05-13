import React, { useState, useEffect, useMemo } from "react";
import { useParams, useNavigate, useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as companyService from "../../company/companyService";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Loader from "../../components/Loader";
import Badge from "../../components/Badge";
import CompanyLogoAvatar from "../../components/CompanyLogoAvatar";

function maskAccountNumber(s) {
  const d = String(s || "").replace(/\s/g, "");
  if (!d) return "—";
  if (d.length <= 4) return "•".repeat(d.length);
  return "•".repeat(d.length - 4) + d.slice(-4);
}

function formatTime(value) {
  const text = String(value || "");
  return text ? text.slice(0, 5) : "—";
}

function toNumber(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function buildOsmEmbedUrl(lat, lng) {
  const delta = 0.015;
  const minLon = lng - delta;
  const minLat = lat - delta;
  const maxLon = lng + delta;
  const maxLat = lat + delta;
  return `https://www.openstreetmap.org/export/embed.html?bbox=${minLon}%2C${minLat}%2C${maxLon}%2C${maxLat}&layer=mapnik&marker=${lat}%2C${lng}`;
}

function boolLabel(v) {
  return v ? "Enabled" : "Disabled";
}

function isCompanyActive(status) {
  if (status === null || status === undefined || status === "") return false;
  if (status === 0 || status === "0" || status === false) return false;
  const s = typeof status === "string" ? status.trim() : status;
  const u = typeof s === "string" ? s.toUpperCase() : s;
  if (u === "D" || u === "I" || u === "INACTIVE" || u === "DISABLED" || u === "N" || u === "NO" || u === "FALSE") return false;
  return status === "A" || status === "Active" || status === 1 || status === "1" || status === true;
}

export default function CompanyDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [company, setCompany] = useState(null);
  const [loading, setLoading] = useState(true);
  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [deactivating, setDeactivating] = useState(false);
  const [showDangerZone, setShowDangerZone] = useState(false);
  const [selectedBranchId, setSelectedBranchId] = useState(null);
  const [lobOptions, setLobOptions] = useState([]);

  useEffect(() => {
    if (!id || !accessToken) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const [companyRes, lobRes] = await Promise.all([
          companyService.getCompany(id, accessToken),
          lobService.getLineOfBusinessDropdowns(accessToken).catch(() => ({})),
        ]);
        if (!cancelled) {
          setCompany(companyRes?.data || null);
          const lobRows = Array.isArray(lobRes?.data) ? lobRes.data : Array.isArray(lobRes) ? lobRes : [];
          setLobOptions(lobRows);
        }
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Error", e.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [id, accessToken]);

  const handleDelete = async () => {
    if (!id || !accessToken) return;
    setDeleting(true);
    try {
      await companyService.deleteCompany(id, accessToken);
      addToastSafe("success", "Deleted", "Company has been deleted.");
      navigate("/company", { replace: true });
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleting(false);
      setDeleteConfirm(false);
    }
  };

  const handleDeactivate = async () => {
    if (!id || !accessToken || !company) return;
    setDeactivating(true);
    try {
      await companyService.updateCompany(id, { company_status: 0 }, accessToken);
      const full = await companyService.getCompany(id, accessToken);
      setCompany(full.data || null);
      addToastSafe("success", "Deactivated", "Company has been deactivated.");
    } catch (e) {
      addToastSafe("error", "Deactivate failed", e.message);
    } finally {
      setDeactivating(false);
    }
  };

  const handleActivate = async () => {
    if (!id || !accessToken || !company) return;
    setDeactivating(true);
    try {
      await companyService.updateCompany(id, { company_status: 1 }, accessToken);
      const full = await companyService.getCompany(id, accessToken);
      setCompany(full.data || null);
      addToastSafe("success", "Activated", "Company has been activated.");
    } catch (e) {
      addToastSafe("error", "Activate failed", e.message);
    } finally {
      setDeactivating(false);
    }
  };

  const companyData = company || {};
  const companyIsActive = isCompanyActive(companyData.company_status);
  const branches = companyData.branches || [];
  const payments = companyData.company_payments || companyData.companyPayments || [];
  const features = companyData.company_features || companyData.companyFeatures || [];
  const taxMasters = companyData.tax_masters || companyData.taxMasters || [];
  const businessHours = companyData.business_hours || companyData.businessHours || [];
  const settings = companyData.settings || {};
  const contactList = branches.map((b) => b.contact).filter(Boolean);
  const contactPrimary = contactList[0] || {};

  const custApp = companyData.customer_app === true || companyData.customer_app === 1 || companyData.customer_app === "1";
  const apptAuto = companyData.appointment_auto_confirm === true || companyData.appointment_auto_confirm === 1 || companyData.appointment_auto_confirm === "1";
  const openDays = businessHours.filter((x) => !!x.is_open).length;
  const branchCount = branches.length;
  const paymentCount = payments.length;
  const featureCount = features.length;
  const taxCount = taxMasters.length;

  const todayName = new Intl.DateTimeFormat("en-US", { weekday: "long" }).format(new Date());
  const todaySlot = businessHours.find((x) => x.day_of_week === todayName && !!x.is_open);
  const dayOrder = { Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6, Sunday: 7 };
  const orderedHours = [...businessHours].sort((a, b) => {
    const da = dayOrder[a.day_of_week] || 99;
    const db = dayOrder[b.day_of_week] || 99;
    if (da !== db) return da - db;
    return (a.slot_index || 1) - (b.slot_index || 1);
  });

  const mappableBranches = useMemo(
    () =>
      branches
        .map((branch) => ({
          ...branch,
          lat: toNumber(branch.latitude),
          lng: toNumber(branch.longitude),
        }))
        .filter((branch) => branch.lat !== null && branch.lng !== null),
    [branches]
  );

  const selectedBranch =
    mappableBranches.find((branch) => String(branch.branch_id) === String(selectedBranchId)) ||
    mappableBranches[0] ||
    null;
  const mapSrc = selectedBranch ? buildOsmEmbedUrl(selectedBranch.lat, selectedBranch.lng) : null;
  const lineOfBusinessName =
    companyData.company_business_name ||
    companyData.line_of_business?.lob_name ||
    companyData.company_business?.lob_name ||
    lobOptions.find((o) => String(o?.lob_id) === String(companyData.company_business_id))?.lob_name ||
    companyData.company_business_id ||
    "—";

  useEffect(() => {
    if (!mappableBranches.length) {
      setSelectedBranchId(null);
      return;
    }
    const exists = mappableBranches.some((branch) => String(branch.branch_id) === String(selectedBranchId));
    if (!exists) {
      setSelectedBranchId(mappableBranches[0].branch_id);
    }
  }, [mappableBranches, selectedBranchId]);

  if (loading || !company) {
    return (
      <div>
        <PageHeader title="Company" breadcrumb="Companies" />
        <Card>
          <Loader />
        </Card>
      </div>
    );
  }

  const shellBg = "#EEF2F8";
  const panelBg = "#FFFFFF";
  const panelBorder = "#DEE6F2";
  const panelText = "#0B1F33";
  const panelMuted = "#5B6B82";
  const headerBg = "linear-gradient(120deg, #0C2238 0%, #133C62 54%, #1C4E78 100%)";
  const sectionTitleStyle = {
    color: panelText,
    fontWeight: TYPE.bold,
    fontFamily: TYPE.fontDisplay,
    letterSpacing: "0.01em",
  };
  const infoLabelStyle = {
    color: panelMuted,
    fontSize: TYPE.xs,
    textTransform: "uppercase",
    letterSpacing: "0.06em",
    fontWeight: TYPE.semibold,
  };
  const infoValueStyle = {
    color: panelText,
    marginTop: 4,
    fontSize: TYPE.sm,
    lineHeight: 1.5,
  };

  return (
    <div style={{ background: shellBg, minHeight: "100vh", paddingBottom: 24 }}>
      <PageHeader
        // title="Company Profile"
        // subtitle={`${company.company_name || "—"}${company.company_code ? ` • ${company.company_code}` : ""}`}
        breadcrumb="Companies"
        action={
          <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
            <Button variant="ghost" onClick={() => navigate("/company")}>
              Back to list
            </Button>
            <Button variant="secondary" icon="✎" onClick={() => navigate(`/company/${id}/edit`)}>
              Edit Company
            </Button>
            <Button variant="secondary" onClick={() => navigate(`/admin/companies/${id}/create-super-user`)}>
              Create super user
            </Button>
            <Button variant="danger" onClick={() => setShowDangerZone((prev) => !prev)}>
              {showDangerZone ? "Hide Danger Zone" : "Danger Zone"}
            </Button>
          </div>
        }
      />

      {!companyIsActive ? (
        <div style={{ padding: "0 20px", marginTop: -8, marginBottom: 4 }}>
          <div
            style={{
              display: "flex",
              flexWrap: "wrap",
              alignItems: "center",
              justifyContent: "space-between",
              gap: 14,
              padding: "14px 18px",
              borderRadius: 14,
              background: "linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%)",
              border: "1px solid #F59E0B",
              boxShadow: "0 8px 24px rgba(245, 158, 11, 0.12)",
            }}
          >
            <div style={{ minWidth: 0, flex: "1 1 240px" }}>
              <div style={{ fontWeight: TYPE.bold, color: "#92400E", fontSize: TYPE.sm, marginBottom: 4 }}>
                Company is inactive
              </div>
              <div style={{ color: "#78350F", fontSize: TYPE.sm, lineHeight: 1.5, fontFamily: TYPE.fontBody }}>
                If this company was turned off from Danger Zone (or otherwise deactivated), you can set it back to{" "}
                <strong>active</strong> at any time—lists, logins, and operations will treat it as live again.
              </div>
            </div>
            <Button variant="success" onClick={handleActivate} disabled={deactivating} style={{ flexShrink: 0 }}>
              {deactivating ? "Updating…" : "Set company to active"}
            </Button>
          </div>
        </div>
      ) : null}

      <div style={{ display: "grid", gap: 20, padding: "0 20px" }}>
        <div
          style={{
            background: headerBg,
            color: "#fff",
            borderRadius: 20,
            padding: "22px 24px",
            boxShadow: "0 20px 60px rgba(11,25,41,0.16)",
            border: "1px solid rgba(255,255,255,0.12)",
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
            <CompanyLogoAvatar name={company.company_name} logoUrls={company.company_logo_urls} size={62} />
            <div style={{ minWidth: 0 }}>
              <div style={{ fontFamily: TYPE.fontDisplay, fontWeight: TYPE.black, fontSize: TYPE.xxl, lineHeight: 1.2 }}>
                {company.company_name || "Company"}
              </div>
              <div style={{ color: "rgba(255,255,255,0.74)", fontSize: TYPE.sm, marginTop: 2 }}>
                {company.tag_line || company.legal_name || "Organization profile"}
              </div>
              <div style={{ marginTop: 10, display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap" }}>
                <Badge status={companyIsActive ? "Active" : "Inactive"} />
                <span style={{ fontSize: TYPE.xs, color: "rgba(255,255,255,0.75)" }}>
                  {company.company_code ? `Company Code: ${company.company_code}` : "Code not assigned"}
                </span>
              </div>
            </div>
          </div>
          <div style={{ marginTop: 14, display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(190px, 1fr))", gap: 10 }}>
            {[
              ["Primary Contact", company.phone_number || contactPrimary.phone || "—"],
              ["Primary Email", company.email || contactPrimary.email || "—"],
              ["Line of Business", lineOfBusinessName],
              ["Address", [contactPrimary.address1, contactPrimary.city, contactPrimary.state].filter(Boolean).join(", ") || "—"],
            ].map(([label, value]) => (
              <div key={label} style={{ background: "rgba(255,255,255,0.12)", borderRadius: 12, padding: "9px 11px", border: "1px solid rgba(255,255,255,0.14)" }}>
                <div style={{ fontSize: TYPE.xs, color: "rgba(255,255,255,0.72)", textTransform: "uppercase", letterSpacing: "0.06em" }}>
                  {label}
                </div>
                <div style={{ fontSize: TYPE.sm, marginTop: 4, color: "#fff", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                  {value}
                </div>
              </div>
            ))}
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16, alignItems: "start" }}>
          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 14 }}>
              <div style={sectionTitleStyle}>Company Information</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                Edit
              </Button>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: 14 }}>
              <div><div style={infoLabelStyle}>Legal Name</div><div style={infoValueStyle}>{company.legal_name || "—"}</div></div>
              <div><div style={infoLabelStyle}>Primary Email</div><div style={infoValueStyle}>{company.email || contactPrimary.email || "—"}</div></div>
              <div><div style={infoLabelStyle}>Phone</div><div style={infoValueStyle}>{company.phone_number || contactPrimary.phone || "—"}</div></div>
              <div><div style={infoLabelStyle}>Website</div><div style={infoValueStyle}>{company.company_website || "—"}</div></div>
              <div><div style={infoLabelStyle}>Line of Business</div><div style={infoValueStyle}>{lineOfBusinessName}</div></div>
              <div><div style={infoLabelStyle}>Primary Address</div><div style={infoValueStyle}>{[contactPrimary.address1, contactPrimary.city, contactPrimary.state, contactPrimary.country].filter(Boolean).join(", ") || "—"}</div></div>
              {company.description ? (
                <div style={{ gridColumn: "1 / -1" }}>
                  <div style={infoLabelStyle}>Description</div>
                  <div style={{ ...infoValueStyle, whiteSpace: "pre-wrap", lineHeight: 1.5 }}>{company.description}</div>
                </div>
              ) : null}
            </div>
          </div>

          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
              <div style={sectionTitleStyle}>Business Hours</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                Manage
              </Button>
            </div>
            <div style={{ marginBottom: 12, color: panelText, fontWeight: TYPE.semibold, background: "#F8FAFE", border: `1px solid ${panelBorder}`, borderRadius: 10, padding: "8px 10px" }}>
              {todaySlot ? `Open Today (${formatTime(todaySlot.opening_time)} - ${formatTime(todaySlot.closing_time)})` : "Closed Today"}
            </div>
            <div style={{ display: "grid", gap: 7 }}>
              {orderedHours.length ? orderedHours.map((bh) => (
                <div key={`${bh.day_of_week}-${bh.slot_index}`} style={{ display: "flex", justifyContent: "space-between", borderBottom: `1px solid ${panelBorder}`, paddingBottom: 7 }}>
                  <span style={{ color: panelText, fontWeight: TYPE.medium }}>
                    {bh.day_of_week}{bh.slot_index > 1 ? ` (slot ${bh.slot_index})` : ""}
                  </span>
                  <span style={{ color: panelMuted }}>
                    {bh.is_open ? `${formatTime(bh.opening_time)} - ${formatTime(bh.closing_time)}` : "Closed"}
                  </span>
                </div>
              )) : <div style={{ color: panelMuted }}>No business hours configured.</div>}
            </div>
          </div>
        </div>

        <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 14, flexWrap: "wrap", gap: 8 }}>
            <div>
              <div style={sectionTitleStyle}>Branch Network</div>
              <div style={{ color: panelMuted, fontSize: TYPE.sm, marginTop: 4 }}>
                Pick a branch with saved coordinates to preview its map. You can add latitude and longitude under Edit company → Branches.
              </div>
            </div>
            <Button variant="secondary" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
              Manage Branches
            </Button>
          </div>
          {branches.length ? (
            <div style={{ display: "grid", gridTemplateColumns: "320px 1fr", gap: 14 }}>
              <div style={{ border: `1px solid ${panelBorder}`, borderRadius: 12, background: "#F8FAFD", maxHeight: 430, overflowY: "auto" }}>
                {branches.map((b) => {
                  const isMapped = toNumber(b.latitude) !== null && toNumber(b.longitude) !== null;
                  const isSelected = selectedBranch && String(selectedBranch.branch_id) === String(b.branch_id);
                  return (
                    <button
                      type="button"
                      key={b.branch_id}
                      onClick={() => isMapped && setSelectedBranchId(b.branch_id)}
                      style={{
                        width: "100%",
                        border: "none",
                        textAlign: "left",
                        background: isSelected ? "#EAF3FF" : "#fff",
                        padding: 12,
                        borderBottom: `1px solid ${panelBorder}`,
                        cursor: isMapped ? "pointer" : "not-allowed",
                        opacity: isMapped ? 1 : 0.55,
                      }}
                    >
                      <div style={{ color: panelText, fontWeight: TYPE.semibold }}>
                        {b.branch_name || `Branch #${b.branch_id}`}
                      </div>
                      <div style={{ color: panelMuted, fontSize: TYPE.xs, marginTop: 3 }}>
                        {b.contact ? [b.contact.address1, b.contact.city, b.contact.state].filter(Boolean).join(", ") : "No address"}
                      </div>
                      <div style={{ color: panelMuted, fontSize: TYPE.xs, marginTop: 4 }}>
                        {isMapped ? `${b.latitude}, ${b.longitude}` : "Map coordinates not available"}
                      </div>
                    </button>
                  );
                })}
              </div>

              <div style={{ border: `1px solid ${panelBorder}`, borderRadius: 12, overflow: "hidden", background: "#fff", minHeight: 430 }}>
                {selectedBranch && mapSrc ? (
                  <div style={{ height: "100%", display: "grid", gridTemplateRows: "1fr auto" }}>
                    <iframe
                      title="Branch map preview"
                      src={mapSrc}
                      style={{ border: 0, width: "100%", minHeight: 360 }}
                      loading="lazy"
                      referrerPolicy="no-referrer-when-downgrade"
                    />
                    <div style={{ borderTop: `1px solid ${panelBorder}`, padding: "10px 12px", display: "flex", justifyContent: "space-between", flexWrap: "wrap", gap: 8 }}>
                      <div style={{ color: panelText, fontWeight: TYPE.semibold }}>
                        {selectedBranch.branch_name || `Branch #${selectedBranch.branch_id}`}
                      </div>
                      <div style={{ color: panelMuted, fontSize: TYPE.sm }}>
                        Lat: {selectedBranch.lat?.toFixed(6)} | Lng: {selectedBranch.lng?.toFixed(6)}
                      </div>
                    </div>
                  </div>
                ) : (
                  <div style={{ display: "flex", alignItems: "center", justifyContent: "center", height: "100%", color: panelMuted }}>
                    Map preview appears when this branch has latitude and longitude saved in branch details.
                  </div>
                )}
              </div>
            </div>
          ) : (
            <div style={{ color: panelMuted }}>No branches configured.</div>
          )}
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
              <div style={sectionTitleStyle}>Bank Information</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                Edit
              </Button>
            </div>
            <div style={{ display: "grid", gap: 8 }}>
              <div><div style={infoLabelStyle}>Bank Name</div><div style={infoValueStyle}>{company.bank_name || "—"}</div></div>
              <div><div style={infoLabelStyle}>Bank Code</div><div style={infoValueStyle}>{company.bank_code || "—"}</div></div>
              <div><div style={infoLabelStyle}>Account Name</div><div style={infoValueStyle}>{company.account_name || "—"}</div></div>
              <div><div style={infoLabelStyle}>Account Number</div><div style={infoValueStyle}>{maskAccountNumber(company.account_number)}</div></div>
            </div>
          </div>

          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
              <div style={sectionTitleStyle}>Company Settings</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                Edit
              </Button>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
              {[
                ["Enforce 2FA", boolLabel(!!settings.enforce_2fa)],
                ["Geo Fencing", boolLabel(!!settings.geo_fencing_enabled)],
                ["Public Page", boolLabel(!!settings.public_company_page)],
                ["Auto Approve", boolLabel(!!settings.auto_approve_appointments)],
                ["Time Slice", settings.appointment_time_slice_enabled ? `${settings.appointment_time_slice_minutes || "—"} min` : "Disabled"],
              ].map(([label, value]) => (
                <div key={label} style={{ border: `1px solid ${panelBorder}`, borderRadius: 10, padding: "8px 10px", background: "#FCFDFF" }}>
                  <div style={{ color: panelMuted, fontSize: TYPE.xs }}>{label}</div>
                  <div style={{ color: panelText, fontWeight: TYPE.semibold, marginTop: 4 }}>{value}</div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
              <div style={sectionTitleStyle}>Enabled Payments</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                View
              </Button>
            </div>
            {payments.length ? (
              <div style={{ display: "grid", gap: 8 }}>
                {payments.map((cp) => (
                  <div key={cp.id || cp.payment_method_id} style={{ border: `1px solid ${panelBorder}`, borderRadius: 10, padding: "8px 10px", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <span style={{ color: panelText, fontWeight: TYPE.medium }}>{cp.payment_method?.payment_name ?? "Payment"}</span>
                    <span style={{ fontSize: TYPE.xs, color: "#0F6D3D", background: "#E6F8EE", borderRadius: 999, padding: "3px 8px" }}>Enabled</span>
                  </div>
                ))}
              </div>
            ) : <div style={{ color: panelMuted }}>No payment methods selected.</div>}
          </div>

          <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
              <div style={sectionTitleStyle}>Activated Features</div>
              <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
                View
              </Button>
            </div>
            {features.length ? (
              <div style={{ display: "grid", gap: 8 }}>
                {features.map((cf) => (
                  <div key={cf.cf_id} style={{ border: `1px solid ${panelBorder}`, borderRadius: 10, padding: "8px 10px", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <span style={{ color: panelText, fontWeight: TYPE.medium }}>{cf.feature?.feature_name ?? "Feature"}</span>
                    <span style={{ fontSize: TYPE.xs, color: "#0A4B78", background: "#E6F1FF", borderRadius: 999, padding: "3px 8px" }}>Active</span>
                  </div>
                ))}
              </div>
            ) : <div style={{ color: panelMuted }}>No features activated.</div>}
          </div>
        </div>

        <div style={{ background: panelBg, border: `1px solid ${panelBorder}`, borderRadius: 16, padding: 18, boxShadow: "0 10px 26px rgba(15,35,63,0.06)" }}>
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 12 }}>
            <div style={sectionTitleStyle}>Tax Slabs Used</div>
            <Button variant="ghost" size="sm" onClick={() => navigate(`/company/${id}/edit`)}>
              View
            </Button>
          </div>
          {taxMasters.length ? (
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: 10 }}>
              {taxMasters.map((tm) => (
                <div key={tm.tax_id} style={{ border: `1px solid ${panelBorder}`, borderRadius: 10, padding: "10px 11px", background: "#FCFDFF" }}>
                  <div style={{ color: panelText, fontWeight: TYPE.semibold }}>{tm.tax_name}</div>
                  <div style={{ color: panelMuted, fontSize: TYPE.xs, marginTop: 4 }}>
                    {(tm.components || []).map((comp) => comp.component_name).filter(Boolean).join(", ") || "No components"}
                  </div>
                </div>
              ))}
            </div>
          ) : <div style={{ color: panelMuted }}>No tax slabs configured.</div>}
        </div>

        {showDangerZone ? (
          <div
            style={{
              background: "linear-gradient(135deg, #FFF0F0, #FFE4E6)",
              border: "1px solid #FECACA",
              borderRadius: 16,
              padding: 18,
            }}
          >
            <div style={{ color: "#B91C1C", fontWeight: TYPE.bold, marginBottom: 6 }}>Danger Zone</div>
            <div style={{ color: "#7F1D1D", fontSize: TYPE.sm, marginBottom: 12 }}>
              These actions impact company access and data visibility. Proceed only if you are sure.
            </div>
            {!companyIsActive ? (
              <div
                style={{
                  color: "#14532D",
                  fontSize: TYPE.sm,
                  marginBottom: 12,
                  padding: "10px 12px",
                  borderRadius: 10,
                  background: "#DCFCE7",
                  border: "1px solid #86EFAC",
                }}
              >
                This company is <strong>inactive</strong>. Use <strong>Activate Company</strong> to remove that inactive state and make it active again (you can also use <strong>Set company to active</strong> at the top of this page).
              </div>
            ) : null}
            <div style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
              <Button
                variant="danger"
                onClick={companyIsActive ? handleDeactivate : handleActivate}
                disabled={deactivating}
              >
                {deactivating ? "Updating..." : companyIsActive ? "Deactivate Company" : "Activate Company"}
              </Button>
              <Button variant="danger" onClick={() => setDeleteConfirm(true)} disabled={deleting}>
                {deleting ? "Deleting..." : "Delete Company"}
              </Button>
            </div>
          </div>
        ) : null}
      </div>

      {deleteConfirm && (
        <div style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.6)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000 }}>
          <div style={{ background: t.bgCard, padding: 24, borderRadius: 12, maxWidth: 400, border: `1px solid ${t.border}` }}>
            <p style={{ color: t.text, marginBottom: 16 }}>Delete this company? This cannot be undone.</p>
            <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
              <Button variant="ghost" onClick={() => setDeleteConfirm(false)} disabled={deleting}>
                Cancel
              </Button>
              <Button variant="danger" onClick={handleDelete} disabled={deleting}>
                {deleting ? "Deleting..." : "Delete"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
