import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as taxTemplateService from "../../tax/taxTemplateService";
import * as companyService from "../../company/companyService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Modal from "../../components/Modal";
import Badge from "../../components/Badge";

const emptyDetail = () => ({
  tax_value: 0,
  tax_start_date: new Date().toISOString().slice(0, 10),
  tax_end_date: "",
});

const emptyComponent = () => ({
  component_name: "",
  details: [emptyDetail()],
});

const REGION_TYPES = ["COUNTRY", "STATE", "CITY"];
const TAX_TYPES = ["GST", "VAT", "SALES_TAX"];
const APPLICABILITY_TYPES = ["FLAT", "INTRA_STATE", "INTER_STATE", "LOCATION_BASED"];

const REGION_TYPE_OPTIONS = REGION_TYPES.map((v) => ({ value: v, label: v }));
const TAX_TYPE_OPTIONS = TAX_TYPES.map((v) => ({ value: v, label: v }));
const APPLICABILITY_OPTIONS = APPLICABILITY_TYPES.map((v) => ({ value: v, label: v.replace(/_/g, " ") }));

/** 8 cols: tax name … version + actions column */
const TABLE_GRID =
  "minmax(180px,1.7fr) minmax(52px,64px) minmax(92px,108px) minmax(76px,88px) minmax(88px,100px) minmax(100px,120px) minmax(44px,52px) 48px";

function hasDetailOverlap(details) {
  const blocks = (details || []).map((d) => {
    const s = new Date(String(d.tax_start_date || "").slice(0, 10)).getTime();
    const endRaw = d.tax_end_date ? String(d.tax_end_date).slice(0, 10) : "";
    const e = endRaw ? new Date(endRaw).getTime() : Number.POSITIVE_INFINITY;
    return { s, e };
  });
  const ok = blocks.every((b) => !Number.isNaN(b.s) && b.e >= b.s);
  if (!ok) return true;
  blocks.sort((a, b) => a.s - b.s);
  for (let i = 1; i < blocks.length; i++) {
    if (blocks[i].s <= blocks[i - 1].e) return true;
  }
  return false;
}

function dash(v) {
  if (v === null || v === undefined || String(v).trim() === "") return "—";
  return String(v);
}

export default function TaxPage() {
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [countries, setCountries] = useState([]);
  const [countryFilter, setCountryFilter] = useState("");
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(false);

  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    country_code: "",
    region_type: "COUNTRY",
    region_code: "",
    tax_type: "SALES_TAX",
    applicability_type: "FLAT",
    tax_name: "",
    version: 1,
    is_active: true,
    components: [emptyComponent()],
  });

  /** View-detail modal */
  const [detailModalOpen, setDetailModalOpen] = useState(false);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailRow, setDetailRow] = useState(null);

  /** ⋮ actions menu — which row id is open */
  const [actionMenuId, setActionMenuId] = useState(null);

  const countryOptions = useMemo(() => {
    const rows = countries || [];
    return [{ value: "", label: "All countries" }, ...rows.map((r) => ({ value: String(r.country_code || "").toUpperCase(), label: `${r.country_name} (${r.country_code})` }))];
  }, [countries]);

  const loadCountries = useCallback(async () => {
    if (!accessToken) return;
    try {
      const res = await companyService.getCountries(accessToken);
      setCountries(res.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    }
  }, [accessToken, addToastSafe]);

  const load = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await taxTemplateService.listTaxTemplates(accessToken, {
        country_code: countryFilter || undefined,
        per_page: 100,
      });
      setList(res.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
      setList([]);
    } finally {
      setLoading(false);
    }
  }, [accessToken, countryFilter, addToastSafe]);

  useEffect(() => {
    loadCountries();
  }, [loadCountries]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (actionMenuId === null) return undefined;
    const onDocMouseDown = (e) => {
      const wrap = document.getElementById(`tax-actions-${actionMenuId}`);
      if (wrap && !wrap.contains(e.target)) {
        setActionMenuId(null);
      }
    };
    document.addEventListener("mousedown", onDocMouseDown);
    return () => document.removeEventListener("mousedown", onDocMouseDown);
  }, [actionMenuId]);

  const openCreate = () => {
    setEditingId(null);
    setForm({
      country_code: countryFilter || "",
      region_type: "COUNTRY",
      region_code: "",
      tax_type: "SALES_TAX",
      applicability_type: "FLAT",
      tax_name: "",
      version: 1,
      is_active: true,
      components: [emptyComponent()],
    });
    setModalOpen(true);
  };

  const openViewByTaxName = async (row) => {
    if (!accessToken || !row?.template_tax_id) return;
    setDetailModalOpen(true);
    setDetailLoading(true);
    setDetailRow(null);
    try {
      const res = await taxTemplateService.getTaxTemplate(row.template_tax_id, accessToken);
      setDetailRow(res.data || row);
    } catch (e) {
      addToastSafe("error", "Failed to load", e.message);
      setDetailModalOpen(false);
    } finally {
      setDetailLoading(false);
    }
  };

  const openEdit = async (row) => {
    if (!accessToken) return;
    try {
      const res = await taxTemplateService.getTaxTemplate(row.template_tax_id, accessToken);
      const d = res.data || row;
      const comps = (d.components || []).length
        ? d.components.map((c) => ({
            component_name: c.component_name || "",
            details: (c.detail_rows || c.detailRows || []).map((dr) => ({
              tax_value: dr.tax_value ?? 0,
              tax_start_date: dr.tax_start_date ? String(dr.tax_start_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
              tax_end_date: dr.tax_end_date ? String(dr.tax_end_date).slice(0, 10) : "",
            })),
          }))
        : [emptyComponent()];
      setEditingId(d.template_tax_id);
      setForm({
        country_code: String(d.country_code || "").toUpperCase(),
        region_type: String(d.region_type || "COUNTRY").toUpperCase(),
        region_code: d.region_code != null ? String(d.region_code).toUpperCase() : "",
        tax_type: String(d.tax_type || "SALES_TAX").toUpperCase(),
        applicability_type: String(d.applicability_type || "FLAT").toUpperCase(),
        tax_name: d.tax_name || "",
        version: d.version ?? 1,
        is_active: !!Number(d.is_active),
        components: comps.length ? comps : [emptyComponent()],
      });
      setModalOpen(true);
      setActionMenuId(null);
    } catch (e) {
      addToastSafe("error", "Load failed", e.message);
    }
  };

  const validateForm = () => {
    const cc = String(form.country_code || "").trim().toUpperCase();
    if (cc.length !== 2) {
      addToastSafe("error", "Validation", "Select or enter a valid 2-letter country code.");
      return false;
    }
    if (!String(form.tax_name || "").trim()) {
      addToastSafe("error", "Validation", "Tax name is required.");
      return false;
    }
    for (let ci = 0; ci < form.components.length; ci++) {
      const comp = form.components[ci];
      if (!String(comp.component_name || "").trim()) {
        addToastSafe("error", "Validation", `Component ${ci + 1}: name is required.`);
        return false;
      }
      const det = comp.details || [];
      if (!det.length) {
        addToastSafe("error", "Validation", `Component ${ci + 1}: add at least one rate row.`);
        return false;
      }
      for (const row of det) {
        if (!row.tax_start_date) {
          addToastSafe("error", "Validation", "Each rate row needs a start date.");
          return false;
        }
      }
      if (hasDetailOverlap(det)) {
        addToastSafe("error", "Validation", `Component "${comp.component_name || ci + 1}": date ranges must not overlap.`);
        return false;
      }
    }
    return true;
  };

  const buildPayload = () => ({
    country_code: String(form.country_code || "").trim().toUpperCase(),
    region_type: form.region_type,
    region_code: form.region_code && String(form.region_code).trim() ? String(form.region_code).trim().toUpperCase() : null,
    tax_type: form.tax_type,
    applicability_type: form.applicability_type,
    tax_name: String(form.tax_name || "").trim(),
    version: Number(form.version) || 1,
    is_active: !!form.is_active,
    components: form.components.map((c) => ({
      component_name: String(c.component_name || "").trim(),
      details: (c.details || []).map((d) => ({
        tax_value: Number(d.tax_value) || 0,
        tax_start_date: d.tax_start_date,
        tax_end_date: d.tax_end_date ? String(d.tax_end_date).slice(0, 10) : null,
      })),
    })),
  });

  const save = async () => {
    if (!accessToken || !validateForm()) return;
    setSaving(true);
    try {
      const payload = buildPayload();
      if (editingId) {
        await taxTemplateService.updateTaxTemplate(editingId, payload, accessToken);
        addToastSafe("success", "Saved", "Tax template updated.");
      } else {
        await taxTemplateService.createTaxTemplate(payload, accessToken);
        addToastSafe("success", "Created", "Tax template created.");
      }
      setModalOpen(false);
      await load();
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSaving(false);
    }
  };

  const deactivate = async (row) => {
    if (!accessToken) return;
    try {
      await taxTemplateService.deactivateTaxTemplate(row.template_tax_id, accessToken);
      addToastSafe("success", "Updated", "Template deactivated.");
      setActionMenuId(null);
      await load();
      if (detailRow && detailRow.template_tax_id === row.template_tax_id) {
        const res = await taxTemplateService.getTaxTemplate(row.template_tax_id, accessToken);
        setDetailRow(res.data || row);
      }
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    }
  };

  const remove = async (row) => {
    if (!accessToken || !window.confirm(`Delete template "${row.tax_name}"?`)) return;
    try {
      await taxTemplateService.deleteTaxTemplate(row.template_tax_id, accessToken);
      addToastSafe("success", "Deleted", "Template removed.");
      setActionMenuId(null);
      if (detailRow && detailRow.template_tax_id === row.template_tax_id) {
        setDetailModalOpen(false);
        setDetailRow(null);
      }
      await load();
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    }
  };

  const updateForm = (patch) => setForm((prev) => ({ ...prev, ...patch }));

  const updateComponent = (idx, patch) =>
    setForm((prev) => ({
      ...prev,
      components: prev.components.map((c, j) => (j === idx ? { ...c, ...patch } : c)),
    }));

  const updateDetail = (ci, di, patch) =>
    setForm((prev) => ({
      ...prev,
      components: prev.components.map((c, j) =>
        j !== ci
          ? c
          : {
              ...c,
              details: c.details.map((d, k) => (k === di ? { ...d, ...patch } : d)),
            }
      ),
    }));

  const cellMuted = {
    fontSize: TYPE.sm,
    color: t.text,
    overflow: "hidden",
    textOverflow: "ellipsis",
    whiteSpace: "nowrap",
    minWidth: 0,
  };

  const headCell = {
    ...cellMuted,
    color: t.textSecondary,
    fontSize: TYPE.xs,
    fontWeight: TYPE.semibold,
    textTransform: "uppercase",
    letterSpacing: "0.06em",
    whiteSpace: "normal",
  };

  const dropdownItemSx = {
    width: "100%",
    display: "block",
    padding: "10px 14px",
    background: "transparent",
    border: "none",
    cursor: "pointer",
    fontFamily: TYPE.fontBody,
    fontSize: TYPE.sm,
    textAlign: "left",
    color: t.text,
    borderBottom: `1px solid ${t.border}`,
  };

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader
        title="Tax templates"
        subtitle="Country-level blueprints cloned into each company’s tax configuration"
        breadcrumb="Tax"
        action={
          <Button variant="primary" icon="+" onClick={openCreate}>
            New template
          </Button>
        }
      />

      <Card>
        <div style={{ display: "flex", gap: 14, alignItems: "flex-end", marginBottom: 16, flexWrap: "wrap" }}>
          <div style={{ minWidth: 260, flex: "0 1 320px" }}>
            <Select label="Country filter" value={countryFilter} onChange={setCountryFilter} options={countryOptions} />
          </div>
          <Button variant="ghost" size="sm" onClick={load} disabled={loading}>
            {loading ? "Loading…" : "Refresh"}
          </Button>
        </div>

        <div style={{ border: `1px solid ${t.border}`, borderRadius: 10, overflow: "hidden" }}>
          <div style={{ overflowX: "auto" }}>
            <div
              style={{
                display: "grid",
                gridTemplateColumns: TABLE_GRID,
                gap: "8px 10px",
                padding: "10px 14px",
                minWidth: 860,
                background: t.bgElevated,
                alignItems: "center",
              }}
            >
              <div style={headCell}>Tax name</div>
              <div style={headCell}>Country</div>
              <div style={headCell}>Region type</div>
              <div style={headCell}>Region</div>
              <div style={headCell}>Tax type</div>
              <div style={headCell}>App. type</div>
              <div style={headCell}>Ver.</div>
              <div style={{ ...headCell, textAlign: "right" }}>Actions</div>
            </div>
            {loading ? (
              <div style={{ padding: 24, color: t.textMuted, borderTop: `1px solid ${t.border}` }}>Loading…</div>
            ) : (list || []).length === 0 ? (
              <div style={{ padding: 24, color: t.textMuted, borderTop: `1px solid ${t.border}` }}>No templates for this filter.</div>
            ) : (
              (list || []).map((row) => (
                <div
                  key={row.template_tax_id}
                  style={{
                    display: "grid",
                    gridTemplateColumns: TABLE_GRID,
                    gap: "8px 10px",
                    padding: "10px 14px",
                    minWidth: 860,
                    alignItems: "center",
                    borderTop: `1px solid ${t.border}`,
                    color: t.text,
                  }}
                >
                  <div style={{ minWidth: 0 }}>
                    <button
                      type="button"
                      onClick={() => openViewByTaxName(row)}
                      title="View tax details"
                      style={{
                        background: "none",
                        border: "none",
                        padding: 0,
                        margin: 0,
                        cursor: "pointer",
                        fontWeight: TYPE.semibold,
                        fontSize: TYPE.sm,
                        color: t.accent,
                        textAlign: "left",
                        maxWidth: "100%",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                        whiteSpace: "nowrap",
                      }}
                    >
                      {dash(row.tax_name)}
                    </button>
                  </div>
                  <div style={{ ...cellMuted, fontFamily: TYPE.fontMono }}>{dash(row.country_code)}</div>
                  <div style={cellMuted}>{dash(row.region_type)}</div>
                  <div style={{ ...cellMuted, fontFamily: TYPE.fontMono }}>{dash(row.region_code)}</div>
                  <div style={cellMuted}>{dash(row.tax_type)}</div>
                  <div style={cellMuted}>
                    {row.applicability_type != null && String(row.applicability_type).trim() !== ""
                      ? String(row.applicability_type).replace(/_/g, " ")
                      : "—"}
                  </div>
                  <div style={{ ...cellMuted, fontFamily: TYPE.fontMono }}>{dash(row.version)}</div>
                  <div style={{ display: "flex", justifyContent: "flex-end", position: "relative" }}>
                    <div id={`tax-actions-${row.template_tax_id}`}>
                      <button
                        type="button"
                        aria-label="Tax template actions"
                        onMouseDown={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                        }}
                        onClick={(e) => {
                          e.stopPropagation();
                          setActionMenuId((prev) => (prev === row.template_tax_id ? null : row.template_tax_id));
                        }}
                        style={{
                          border: `1px solid ${t.border}`,
                          background: t.bgHover,
                          borderRadius: 8,
                          color: t.textMuted,
                          width: 32,
                          height: 28,
                          cursor: "pointer",
                          fontSize: 16,
                          lineHeight: "22px",
                          padding: 0,
                        }}
                      >
                        ⋮
                      </button>
                      {actionMenuId === row.template_tax_id && (
                        <div
                          role="menu"
                          onMouseDown={(e) => e.stopPropagation()}
                          style={{
                            position: "absolute",
                            right: 0,
                            top: "100%",
                            marginTop: 4,
                            minWidth: 164,
                            background: t.bgCard,
                            border: `1px solid ${t.borderStrong}`,
                            borderRadius: 10,
                            boxShadow: "0 20px 60px rgba(0,0,0,0.7)",
                            zIndex: 200,
                            overflow: "hidden",
                          }}
                        >
                          <button
                            type="button"
                            role="menuitem"
                            style={dropdownItemSx}
                            onClick={() => {
                              setActionMenuId(null);
                              openEdit(row);
                            }}
                          >
                            Edit
                          </button>
                          {Number(row.is_active) ? (
                            <button
                              type="button"
                              role="menuitem"
                              style={dropdownItemSx}
                              onClick={() => {
                                setActionMenuId(null);
                                deactivate(row);
                              }}
                            >
                              Deactivate
                            </button>
                          ) : null}
                          <button
                            type="button"
                            role="menuitem"
                            style={{ ...dropdownItemSx, borderBottom: "none", color: t.danger }}
                            onClick={() => {
                              setActionMenuId(null);
                              remove(row);
                            }}
                          >
                            Delete
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </Card>
      <Modal
        open={detailModalOpen}
        onClose={() => !detailLoading && setDetailModalOpen(false)}
        title="Tax template details"
        width={640}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          {detailLoading ? (
            <div style={{ color: t.textMuted, fontSize: TYPE.sm }}>Loading…</div>
          ) : detailRow ? (
            <>
              <div style={{ display: "flex", alignItems: "center", gap: 12, flexWrap: "wrap" }}>
                <div style={{ fontWeight: TYPE.bold, fontSize: TYPE.lg, color: t.text }}>{detailRow.tax_name}</div>
                <Badge status={Number(detailRow.is_active) ? "Active" : "Inactive"}>{Number(detailRow.is_active) ? "Active" : "Inactive"}</Badge>
              </div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12, fontSize: TYPE.sm }}>
                <div>
                  <span style={{ color: t.textSecondary }}>Country</span>
                  <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{dash(detailRow.country_code)}</div>
                </div>
                <div>
                  <span style={{ color: t.textSecondary }}>Region type</span>
                  <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{dash(detailRow.region_type)}</div>
                </div>
                <div>
                  <span style={{ color: t.textSecondary }}>Region</span>
                  <div style={{ fontWeight: TYPE.semibold, fontFamily: TYPE.fontMono, color: t.text }}>{dash(detailRow.region_code)}</div>
                </div>
                <div>
                  <span style={{ color: t.textSecondary }}>Tax type</span>
                  <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{dash(detailRow.tax_type)}</div>
                </div>
                <div>
                  <span style={{ color: t.textSecondary }}>Applicability</span>
                  <div style={{ fontWeight: TYPE.semibold, color: t.text }}>
                    {detailRow.applicability_type != null && String(detailRow.applicability_type).trim() !== ""
                      ? String(detailRow.applicability_type).replace(/_/g, " ")
                      : "—"}
                  </div>
                </div>
                <div>
                  <span style={{ color: t.textSecondary }}>Version</span>
                  <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{dash(detailRow.version)}</div>
                </div>
              </div>
              <div style={{ borderTop: `1px solid ${t.border}`, paddingTop: 12 }}>
                <div style={{ color: t.textSecondary, fontSize: TYPE.xs, fontWeight: TYPE.semibold, marginBottom: 8 }}>Components</div>
                {(detailRow.components || []).map((c, i) => (
                  <div
                    key={i}
                    style={{ marginBottom: 12, padding: 12, borderRadius: 8, border: `1px solid ${t.border}`, background: t.bgElevated }}
                  >
                    <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{dash(c.component_name)}</div>
                    <div style={{ marginTop: 8, overflowX: "auto" }}>
                      <table style={{ width: "100%", borderCollapse: "collapse", fontSize: TYPE.sm }}>
                        <thead>
                          <tr style={{ color: t.textSecondary, textAlign: "left" }}>
                            <th style={{ padding: "6px 8px", borderBottom: `1px solid ${t.border}` }}>Rate %</th>
                            <th style={{ padding: "6px 8px", borderBottom: `1px solid ${t.border}` }}>Start</th>
                            <th style={{ padding: "6px 8px", borderBottom: `1px solid ${t.border}` }}>End</th>
                          </tr>
                        </thead>
                        <tbody>
                          {(c.detail_rows || c.detailRows || []).map((dr, j) => (
                            <tr key={j}>
                              <td style={{ padding: "8px", color: t.text }}>{dr.tax_value ?? "—"}</td>
                              <td style={{ padding: "8px", color: t.text }}>{dash(dr.tax_start_date)}</td>
                              <td style={{ padding: "8px", color: t.text }}>{dr.tax_end_date ? String(dr.tax_end_date).slice(0, 10) : "—"}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                      {!(c.detail_rows || c.detailRows || []).length ? <div style={{ color: t.textMuted, fontSize: TYPE.sm }}>No rate rows.</div> : null}
                    </div>
                  </div>
                ))}
              </div>
              <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", flexWrap: "wrap" }}>
                <Button variant="ghost" onClick={() => setDetailModalOpen(false)}>
                  Close
                </Button>
                <Button
                  variant="primary"
                  size="sm"
                  onClick={() => {
                    setDetailModalOpen(false);
                    openEdit(detailRow);
                  }}
                >
                  Edit
                </Button>
              </div>
            </>
          ) : (
            <div style={{ color: t.textMuted }}>No data.</div>
          )}
        </div>
      </Modal>

      <Modal open={modalOpen} onClose={() => !saving && setModalOpen(false)} title={editingId ? "Edit tax template" : "New tax template"} width={760}>
        <div style={{ display: "flex", flexDirection: "column", gap: 14, maxHeight: "70vh", overflowY: "auto" }}>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
            <Select
              label="Country"
              value={String(form.country_code || "").toUpperCase()}
              onChange={(v) => updateForm({ country_code: v })}
              options={[{ value: "", label: "Select…" }, ...countries.map((r) => ({ value: String(r.country_code || "").toUpperCase(), label: `${r.country_name}` }))]}
            />
            <Input label="Tax name" value={form.tax_name} onChange={(v) => updateForm({ tax_name: v })} />
            <Select label="Region type" value={form.region_type} onChange={(v) => updateForm({ region_type: v })} options={REGION_TYPE_OPTIONS} />
            <Input label="Region code (optional)" value={form.region_code} onChange={(v) => updateForm({ region_code: v })} placeholder="e.g. ON, BC" />
            <Select label="Tax type" value={form.tax_type} onChange={(v) => updateForm({ tax_type: v })} options={TAX_TYPE_OPTIONS} />
            <Select
              label="Applicability type"
              value={form.applicability_type}
              onChange={(v) => updateForm({ applicability_type: v })}
              options={APPLICABILITY_OPTIONS}
            />
            <Input label="Version" value={String(form.version)} onChange={(v) => updateForm({ version: Number(v) || 1 })} type="number" />
            <label style={{ display: "flex", alignItems: "center", gap: 10, marginTop: 22, color: t.text }}>
              <input type="checkbox" checked={!!form.is_active} onChange={(e) => updateForm({ is_active: e.target.checked })} />
              Active
            </label>
          </div>

          {form.components.map((comp, ci) => (
            <div key={ci} style={{ border: `1px solid ${t.border}`, borderRadius: 10, padding: 12 }}>
              <div style={{ display: "flex", gap: 10, alignItems: "flex-end", marginBottom: 10 }}>
                <Input
                  label={`Component ${ci + 1}`}
                  value={comp.component_name}
                  onChange={(v) => updateComponent(ci, { component_name: v })}
                  style={{ flex: 1 }}
                />
                <Button variant="ghost" size="sm" onClick={() => setForm((p) => ({ ...p, components: p.components.filter((_, j) => j !== ci) }))} disabled={form.components.length <= 1}>
                  Remove
                </Button>
              </div>
              {(comp.details || []).map((d, di) => (
                <div
                  key={di}
                  style={{
                    display: "grid",
                    gridTemplateColumns: "100px 1fr 1fr 1fr auto",
                    gap: 8,
                    alignItems: "end",
                    marginBottom: 8,
                  }}
                >
                  <Input label="Rate %" value={String(d.tax_value)} onChange={(v) => updateDetail(ci, di, { tax_value: v })} />
                  <Input label="Start" type="date" value={d.tax_start_date} onChange={(v) => updateDetail(ci, di, { tax_start_date: v })} />
                  <Input label="End" type="date" value={d.tax_end_date || ""} onChange={(v) => updateDetail(ci, di, { tax_end_date: v })} />
                  <div />
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                      updateComponent(ci, {
                        details: comp.details.filter((_, k) => k !== di),
                      })
                    }
                    disabled={(comp.details || []).length <= 1}
                  >
                    ×
                  </Button>
                </div>
              ))}
              <Button variant="ghost" size="sm" onClick={() => updateComponent(ci, { details: [...(comp.details || []), emptyDetail()] })}>
                + Rate row
              </Button>
            </div>
          ))}
          <Button variant="ghost" onClick={() => setForm((p) => ({ ...p, components: [...p.components, emptyComponent()] }))}>
            + Component
          </Button>
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 8, paddingTop: 12, borderTop: `1px solid ${t.border}` }}>
            <Button variant="ghost" onClick={() => setModalOpen(false)} disabled={saving}>
              Cancel
            </Button>
            <Button variant="primary" onClick={save} disabled={saving}>
              {saving ? "Saving…" : "Save"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
