import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as catalogueService from "../../catalogue/catalogueService";
import * as companyService from "../../company/companyService";
import * as lineOfBusinessService from "../../lineOfBusiness/lineOfBusinessService";
import * as productBulkUploadService from "../../catalogue/productBulkUploadService";
import * as productTemplateService from "../../catalogue/productTemplateService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Modal from "../../components/Modal";

const TABLE_GRID =
  "minmax(140px,1.5fr) 88px minmax(120px,1fr) 100px minmax(120px,0.85fr) minmax(172px,1fr)";

function suggestedTemplateFilename(row) {
  let base = String(row.company_name || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
  if (!base) base = `company-${row.company_id}`;
  base = base.slice(0, 60);
  return `${base}-catalogue-${row.catalogue_id}-product-upload-template.xlsx`;
}

/** API may return snake_case or camelCase counts depending on middleware. */
function bulkUploadResultCounts(d) {
  if (!d || typeof d !== "object") return { total_rows: undefined, valid_rows: undefined, error_rows: undefined };
  return {
    total_rows: d.total_rows ?? d.totalRows,
    valid_rows: d.valid_rows ?? d.validRows,
    error_rows: d.error_rows ?? d.errorRows,
  };
}

export default function CataloguePage() {
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [searchDebounce, setSearchDebounce] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  const [createOpen, setCreateOpen] = useState(false);
  const [creating, setCreating] = useState(false);
  const [companyChoices, setCompanyChoices] = useState([]);
  const [lobChoices, setLobChoices] = useState([]);
  const [createForm, setCreateForm] = useState({
    company_id: "",
    lob_id: "",
    catalogue_status: "A",
  });

  const [downloadingKey, setDownloadingKey] = useState(null);

  const [bulkOpen, setBulkOpen] = useState(false);
  const [bulkRow, setBulkRow] = useState(null);
  const [bulkFile, setBulkFile] = useState(null);
  const [bulkActingUserId, setBulkActingUserId] = useState("");
  const [bulkBusy, setBulkBusy] = useState(false);
  const [bulkLastSummary, setBulkLastSummary] = useState(null);
  const [bulkPollTok, setBulkPollTok] = useState(null);

  const fetchList = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await catalogueService.listCatalogues(
        {
          page,
          per_page: 15,
          search: searchDebounce.trim() || undefined,
          catalogue_status: statusFilter,
        },
        accessToken
      );
      setList(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (e) {
      setList([]);
      addToastSafe("error", "Catalogues", e.message);
    } finally {
      setLoading(false);
    }
  }, [accessToken, page, searchDebounce, statusFilter, addToastSafe]);

  useEffect(() => {
    fetchList();
  }, [fetchList]);

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounce(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [searchDebounce, statusFilter]);

  const loadCompanyDropdown = useCallback(async () => {
    if (!accessToken) return;
    try {
      const res = await companyService.listCompanies({ per_page: 100 }, accessToken);
      setCompanyChoices(
        (res.data || []).map((c) => ({
          value: String(c.company_id),
          label: `${c.company_name || "Company"} (${c.company_id})`,
        }))
      );
    } catch (_) {
      setCompanyChoices([]);
    }
  }, [accessToken]);

  const loadLobDropdown = useCallback(async () => {
    if (!accessToken) return;
    try {
      const res = await lineOfBusinessService.listLineOfBusiness({ per_page: 100 }, accessToken);
      setLobChoices(
        (res.data || []).map((lob) => ({
          value: String(lob.lob_id),
          label: lob.lob_name ? `${lob.lob_name} (${lob.lob_id})` : String(lob.lob_id),
        }))
      );
    } catch (_) {
      setLobChoices([]);
    }
  }, [accessToken]);

  useEffect(() => {
    if (!createOpen) return;
    loadCompanyDropdown();
    loadLobDropdown();
  }, [createOpen, loadCompanyDropdown, loadLobDropdown]);

  async function submitCreateCatalogue(e) {
    e?.preventDefault?.();
    if (!accessToken) return;
    const companyId = createForm.company_id.trim();
    const lobId = createForm.lob_id.trim();
    if (!companyId || !lobId) {
      addToastSafe("error", "Form", "Select company and line of business.");
      return;
    }
    setCreating(true);
    try {
      await catalogueService.createCatalogue(
        {
          company_id: parseInt(companyId, 10),
          lob_id: parseInt(lobId, 10),
          catalogue_status: createForm.catalogue_status || "A",
        },
        accessToken
      );
      addToastSafe("success", "Catalogue", "Catalogue created.");
      setCreateOpen(false);
      setCreateForm({ company_id: "", lob_id: "", catalogue_status: "A" });
      const listParams = {
        page: 1,
        per_page: 15,
        search: searchDebounce.trim() || undefined,
        catalogue_status: statusFilter,
      };
      const refreshed = await catalogueService.listCatalogues(listParams, accessToken);
      setList(refreshed.data || []);
      if (refreshed.meta) setMeta(refreshed.meta);
      setPage(1);
    } catch (err) {
      addToastSafe("error", "Create failed", err.message);
    } finally {
      setCreating(false);
    }
  }

  async function handleDownload(row) {
    if (!accessToken || !row.can_download_product_template) return;
    const key = `${row.company_id}:${row.catalogue_id}`;
    setDownloadingKey(key);
    try {
      const fallback = suggestedTemplateFilename(row);
      const { blob, filename } = await productTemplateService.downloadProductBulkTemplate(
        row.company_id,
        row.catalogue_id,
        accessToken,
        fallback
      );
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename || fallback;
      a.click();
      URL.revokeObjectURL(url);
      addToastSafe("success", "Download", `${filename || fallback} saved.`);
    } catch (err) {
      addToastSafe("error", "Download failed", err.message);
    } finally {
      setDownloadingKey(null);
    }
  }

  async function pollBulkResult(token) {
    if (!accessToken) return null;
    try {
      const r = await productBulkUploadService.bulkUploadPollResult(token, accessToken);
      if (!r.ok || !r.data) return null;

      return r.data;
    } catch (_) {
      return null;
    }
  }

  useEffect(() => {
    if (!bulkPollTok || !accessToken) return undefined;

    let iv;

    const finishPoll = async (token) => {
      const data = await pollBulkResult(token);
      if (!data) return false;
      if (data.status === "PROCESSING") return false;

      setBulkPollTok(null);
      const cnt = bulkUploadResultCounts(data);
      setBulkLastSummary({
        status: data.status,
        inserted: data.inserted,
        failed: data.failed ?? data.persist_errors?.length ?? 0,
        total_rows: cnt.total_rows,
        valid_rows: cnt.valid_rows,
        error_rows: cnt.error_rows,
        errors_preview: Array.isArray(data.errors) ? data.errors.slice(0, 50) : [],
        csv: typeof data.error_report_csv === "string" ? data.error_report_csv : "",
        hint: data.hint || null,
      });
      if (data.status === "COMPLETED") {
        addToastSafe("success", "Bulk import finished", `${data.inserted ?? 0} products inserted.`);
        fetchList();
      } else {
        addToastSafe("error", "Bulk import", data.message || "Import failed.");
      }
      return true;
    };

    const tick = async () => {
      const done = await finishPoll(bulkPollTok);
      if (done && iv) clearInterval(iv);
    };

    iv = setInterval(() => void tick(), 2500);
    void tick();

    return () => {
      if (iv) clearInterval(iv);
    };
  }, [bulkPollTok, accessToken, fetchList, addToastSafe]);

  async function submitBulkDryRun(e) {
    e?.preventDefault?.();
    if (!accessToken || !bulkRow || !bulkFile) {
      addToastSafe("error", "Bulk upload", "Select an .xlsx file.");
      return;
    }
    setBulkBusy(true);
    try {
      const fd = new FormData();
      fd.append("file", bulkFile);
      fd.append("company_id", String(bulkRow.company_id));
      fd.append("catalogue_id", String(bulkRow.catalogue_id));
      fd.append("dry_run", "1");
      const actingTrim = bulkActingUserId.trim();
      if (actingTrim !== "") fd.append("acting_user_id", actingTrim);
      const { ok, data, raw } = await productBulkUploadService.bulkUploadProducts(fd, accessToken);
      if (!ok) throw new Error(raw?.message || data?.message || "Validation failed.");
      const d = raw?.data ?? data ?? {};
      const cnt = bulkUploadResultCounts(d);
      setBulkLastSummary({
        status: d.status ?? "COMPLETED",
        inserted: d.inserted ?? 0,
        failed: d.failed ?? 0,
        total_rows: cnt.total_rows,
        valid_rows: cnt.valid_rows,
        error_rows: cnt.error_rows,
        errors_preview: Array.isArray(d.errors) ? d.errors.slice(0, 80) : [],
        csv: d.error_report_csv || "",
        hint: d.hint || null,
      });
      const totalN = cnt.total_rows ?? 0;
      const validN = cnt.valid_rows ?? 0;
      const errorN = cnt.error_rows ?? 0;
      if (validN === 0 && totalN > 0) {
        addToastSafe(
          "error",
          "Validation — no rows passed",
          d.hint || "Every row failed checks. Expand errors preview or download the CSV."
        );
      } else if (totalN === 0) {
        addToastSafe("error", "No rows detected", d.hint || "The spreadsheet may not match the template layout.");
      } else {
        addToastSafe("success", "Validation ready", `${validN} valid · ${errorN} error rows`);
      }
    } catch (err) {
      addToastSafe("error", "Validation failed", err.message);
    } finally {
      setBulkBusy(false);
    }
  }

  async function submitBulkImport(e) {
    e?.preventDefault?.();
    if (!accessToken || !bulkRow || !bulkFile) {
      addToastSafe("error", "Bulk upload", "Select an .xlsx file.");
      return;
    }
    setBulkBusy(true);
    try {
      const fd = new FormData();
      fd.append("file", bulkFile);
      fd.append("company_id", String(bulkRow.company_id));
      fd.append("catalogue_id", String(bulkRow.catalogue_id));
      fd.append("dry_run", "0");
      const actingTrim = bulkActingUserId.trim();
      if (actingTrim !== "") fd.append("acting_user_id", actingTrim);
      const res = await productBulkUploadService.bulkUploadProducts(fd, accessToken);
      const inner = res.data ?? res.raw?.data ?? {};
      if (inner.status === "QUEUED" || res.status === 202) {
        const tok =
          typeof inner.result_token === "string"
            ? inner.result_token
            : typeof inner.token === "string"
              ? inner.token
              : "";
        if (!tok) throw new Error("Queued response missing token.");
        setBulkPollTok(tok);
        setBulkLastSummary({
          status: "QUEUED",
          message: inner.message || "Import queued. Polling progress…",
        });
        addToastSafe("success", "Bulk import", "Queued for background processing.");
        return;
      }
      if (!res.ok) throw new Error(res.raw?.message || inner.message || "Import failed.");
      const d = inner;
      const cnt = bulkUploadResultCounts(d);
      setBulkLastSummary({
        status: d.status ?? "COMPLETED",
        inserted: d.inserted ?? 0,
        failed: d.failed ?? 0,
        total_rows: cnt.total_rows,
        valid_rows: cnt.valid_rows,
        error_rows: cnt.error_rows,
        errors_preview: Array.isArray(d.errors) ? d.errors.slice(0, 120) : [],
        csv: d.error_report_csv || "",
        hint: d.hint || null,
      });
      const ins = d.inserted ?? 0;
      if (ins === 0) {
        const vr = cnt.valid_rows ?? 0;
        const title = vr === 0 ? "No products imported" : "Import finished — nothing saved";
        addToastSafe("error", title, d.hint || "Nothing was written to the database. Review errors below or download the CSV.");
      } else {
        addToastSafe("success", "Bulk import finished", `${ins} product(s) inserted.`);
      }
      fetchList();
    } catch (err) {
      addToastSafe("error", "Bulk import failed", err.message);
    } finally {
      setBulkBusy(false);
    }
  }

  function openBulkModal(row) {
    setBulkRow(row);
    setBulkFile(null);
    setBulkActingUserId("");
    setBulkLastSummary(null);
    setBulkPollTok(null);
    setBulkOpen(true);
  }

  function downloadBulkCsv() {
    if (!bulkLastSummary?.csv) return;
    productBulkUploadService.downloadErrorReportCsv(
      bulkLastSummary.csv,
      `bulk-products-${bulkRow?.company_id}-${bulkRow?.catalogue_id}`
    );
  }

  const statusBadge = (active) =>
    active ? <Badge variant="success" label="Active" /> : <Badge variant="default" label="Inactive" />;

  const filtersRow = (
    <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1fr) 180px auto", gap: 12, marginBottom: 16 }}>
      <Input label="Search" value={search} onChange={(v) => setSearch(v)} placeholder="Company name or catalogue ID" />
      <Select
        label="Catalogue status"
        value={statusFilter}
        onChange={setStatusFilter}
        options={[
          { value: "all", label: "All" },
          { value: "active", label: "Active" },
          { value: "inactive", label: "Inactive" },
        ]}
      />
      <div style={{ display: "flex", alignItems: "flex-end" }}>
        <Button variant="primary" type="button" onClick={() => setCreateOpen(true)}>
          Create catalogue
        </Button>
      </div>
    </div>
  );

  const pageInfo = useMemo(
    () => `Page ${meta.current_page ?? 1} / ${Math.max(meta.last_page ?? 1, 1)} · ${meta.total ?? 0} catalogues`,
    [meta]
  );

  const headStyle = useMemo(
    () => ({
      fontSize: TYPE.xs,
      fontWeight: TYPE.semibold,
      color: t.textSecondary,
      textTransform: "uppercase",
      letterSpacing: "0.06em",
      paddingBottom: 8,
      borderBottom: `1px solid ${t.border}`,
    }),
    [t.border, t.textSecondary]
  );

  return (
    <div style={{ fontFamily: TYPE.fontBody, color: t.text }}>
      <PageHeader
        title="Catalogue"
        subtitle="Merchant catalogues by company · product upload template · active / inactive"
        breadcrumb="Catalogue"
      />

      <Card title="Merchant catalogues">
        <p style={{ margin: "0 0 8px", fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.5 }}>
          Templates work even when no taxes exist (leave tax_id blank for non-tax lines). Downloads require at least one active branch because stock is booked per branch.
          File names use the company name for clarity. Each row’s branch id ties products to that branch in the app catalogue unless the client requests all branches.
        </p>
        {filtersRow}

        <div style={{ overflowX: "auto", borderRadius: 8 }}>
          <div
            style={{
              display: "grid",
              gridTemplateColumns: TABLE_GRID,
              gap: "0 12px",
              minWidth: 720,
              alignItems: "center",
              paddingBottom: 6,
              ...headStyle,
            }}
          >
            <span>Company</span>
            <span style={{ justifySelf: "end" }}>ID</span>
            <span>Line of business</span>
            <span>Status</span>
            <span>Template prep</span>
            <span style={{ justifySelf: "end" }}>Actions</span>
          </div>
          {loading ? (
            <p style={{ color: t.textMuted, padding: "16px 0" }}>Loading catalogues…</p>
          ) : list.length === 0 ? (
            <p style={{ color: t.textMuted, padding: "16px 0" }}>No catalogues match your filters.</p>
          ) : (
            list.map((row) => (
              <div
                key={row.catalogue_id}
                style={{
                  display: "grid",
                  gridTemplateColumns: TABLE_GRID,
                  gap: "0 12px",
                  minWidth: 720,
                  alignItems: "center",
                  padding: "10px 0",
                  borderBottom: `1px solid ${t.border}`,
                  fontSize: TYPE.sm,
                }}
              >
                <div style={{ overflow: "hidden" }}>
                  <div style={{ fontWeight: TYPE.semibold, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                    {row.company_name || "—"}
                  </div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted }}>Company #{row.company_id}</div>
                </div>
                <span style={{ justifySelf: "end", color: t.textSecondary }}>{row.catalogue_id}</span>
                <span style={{ color: row.lob_name ? t.textSecondary : t.textMuted }}>{row.lob_name || `LOB ${row.lob_id ?? "—"}`}</span>
                <span>{row.catalogue_active ? <Badge status="Active">Active</Badge> : <Badge>Inactive</Badge>}</span>
                <span style={{ fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.4 }}>
                  {row.can_download_product_template ? (
                    <>
                      Taxes: <strong>{row.tax_count ?? 0}</strong> (optional for import rows)
                      <br />
                      Branches: <strong>{row.branch_count ?? 0}</strong>
                    </>
                  ) : (
                    <>
                      Add at least one active branch for this tenant
                      <br />
                      ({row.tax_count ?? 0} taxes · {row.branch_count ?? 0} branches)
                    </>
                  )}
                </span>
                <div style={{ justifySelf: "end", display: "flex", flexDirection: "column", alignItems: "stretch", gap: 6 }}>
                  <Button
                    variant="secondary"
                    size="sm"
                    disabled={!row.can_download_product_template || downloadingKey === `${row.company_id}:${row.catalogue_id}`}
                    title={
                      row.can_download_product_template
                        ? "Download Excel product upload template"
                        : "Add at least one active branch before downloading the template."
                    }
                    onClick={() => handleDownload(row)}
                  >
                    {downloadingKey === `${row.company_id}:${row.catalogue_id}` ? "…" : "Template"}
                  </Button>
                  <Button variant="ghost" size="sm" disabled={!row.can_download_product_template} onClick={() => openBulkModal(row)}>
                    Import
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>

        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            marginTop: 16,
            flexWrap: "wrap",
            gap: 12,
          }}
        >
          <span style={{ fontSize: TYPE.xs, color: t.textMuted }}>{pageInfo}</span>
          <div style={{ display: "flex", gap: 8 }}>
            <Button variant="ghost" size="sm" disabled={page <= 1 || loading} onClick={() => setPage((p) => Math.max(1, p - 1))}>
              Previous
            </Button>
            <Button variant="ghost" size="sm" disabled={page >= (meta.last_page ?? 1) || loading} onClick={() => setPage((p) => p + 1)}>
              Next
            </Button>
          </div>
        </div>
      </Card>

      <Modal open={createOpen} onClose={() => !creating && setCreateOpen(false)} title="Create catalogue">
        <form onSubmit={submitCreateCatalogue} style={{ display: "grid", gap: 16 }}>
          <Select
            label="Company"
            value={createForm.company_id}
            disabled={creating}
            onChange={(v) => setCreateForm((f) => ({ ...f, company_id: v }))}
            options={[{ value: "", label: companyChoices.length ? "Select company" : "Loading…" }, ...companyChoices]}
          />
          <Select
            label="Line of business"
            value={createForm.lob_id}
            disabled={creating}
            onChange={(v) => setCreateForm((f) => ({ ...f, lob_id: v }))}
            options={[{ value: "", label: lobChoices.length ? "Select LOB" : "Loading…" }, ...lobChoices]}
          />
          <Select
            label="Initial catalogue status"
            value={createForm.catalogue_status}
            disabled={creating}
            onChange={(v) => setCreateForm((f) => ({ ...f, catalogue_status: v }))}
            options={[
              { value: "A", label: "Active (A)" },
              { value: "I", label: "Inactive (I)" },
            ]}
          />
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
            <Button variant="ghost" type="button" disabled={creating} onClick={() => setCreateOpen(false)}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" disabled={creating}>
              {creating ? "Saving…" : "Create catalogue"}
            </Button>
          </div>
        </form>
      </Modal>

      <Modal
        open={bulkOpen}
        onClose={() => !bulkBusy && !bulkPollTok && setBulkOpen(false)}
        title={bulkRow ? `Bulk upload — catalogue #${bulkRow.catalogue_id}` : "Bulk upload"}
      >
        {bulkRow && (
          <form style={{ display: "grid", gap: 14, maxWidth: 520 }}>
            <p style={{ margin: 0, fontSize: TYPE.sm, color: t.textSecondary, lineHeight: 1.5 }}>
              Uses the exported <strong>Products_Upload</strong> (.xlsx) file. Validate first, then confirm import — you can reuse the
              selected file until you close this dialog.
            </p>
            <div style={{ fontSize: TYPE.xs, color: t.textMuted }}>
              Company #{bulkRow.company_id} · {bulkRow.company_name || "Tenant"}
            </div>
            <div>
              <div style={{ fontSize: TYPE.xs, marginBottom: 6, fontWeight: TYPE.semibold, color: t.textSecondary }}>Excel file (.xlsx)</div>
              <input
                type="file"
                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                disabled={bulkBusy || !!bulkPollTok}
                onChange={(e) => {
                  const f = e.target.files?.[0] || null;
                  setBulkFile(f);
                  setBulkLastSummary(null);
                }}
              />
            </div>
            <Input
              label="ERP acting user ID (stock + tags creator; optional)"
              value={bulkActingUserId}
              disabled={bulkBusy || !!bulkPollTok}
              onChange={(v) => setBulkActingUserId(v)}
              placeholder="Leave blank → 0 (system)"
            />
            <div style={{ display: "flex", flexWrap: "wrap", gap: 10 }}>
              <Button variant="secondary" type="button" disabled={bulkBusy || !!bulkPollTok || !bulkFile} onClick={submitBulkDryRun}>
                {bulkBusy ? "…" : "Validate only"}
              </Button>
              <Button variant="primary" type="button" disabled={bulkBusy || !!bulkPollTok || !bulkFile} onClick={submitBulkImport}>
                Confirm import
              </Button>
              {bulkLastSummary?.csv ? (
                <Button variant="ghost" type="button" onClick={() => downloadBulkCsv()}>
                  Download error CSV
                </Button>
              ) : null}
            </div>
            {bulkLastSummary?.hint ? (
              <p style={{ margin: 0, fontSize: TYPE.sm, color: t.accent, lineHeight: 1.45 }}>{bulkLastSummary.hint}</p>
            ) : null}
            {bulkPollTok ? <p style={{ margin: 0, fontSize: TYPE.sm, color: t.accent }}>Polling async import …</p> : null}
            {bulkLastSummary?.status === "QUEUED" && bulkPollTok ? (
              <p style={{ margin: 0, fontSize: TYPE.sm, color: t.textSecondary }}>{bulkLastSummary.message}</p>
            ) : null}
            {bulkLastSummary && bulkLastSummary.status !== "QUEUED" ? (
              <div
                style={{
                  padding: "10px 12px",
                  borderRadius: 10,
                  border: `1px solid ${t.border}`,
                  background: "rgba(255,255,255,0.03)",
                }}
              >
                <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 8 }}>Latest result · {bulkLastSummary.status}</div>
                <div style={{ display: "grid", gap: 4, fontSize: TYPE.sm }}>
                  <span>
                    Total rows parsed: <strong>{bulkLastSummary.total_rows ?? "—"}</strong>
                  </span>
                  <span>
                    Valid rows: <strong>{bulkLastSummary.valid_rows ?? "—"}</strong>
                  </span>
                  <span>
                    Validation errors: <strong>{bulkLastSummary.error_rows ?? "—"}</strong>
                  </span>
                  <span>
                    Inserted: <strong>{bulkLastSummary.inserted ?? 0}</strong> · Persist failures:{" "}
                    <strong>{bulkLastSummary.failed ?? 0}</strong>
                  </span>
                </div>
                {(bulkLastSummary.errors_preview ?? []).length > 0 ? (
                  <details style={{ marginTop: 10 }}>
                    <summary style={{ cursor: "pointer", fontSize: TYPE.sm }}>Row errors preview</summary>
                    <pre
                      style={{
                        whiteSpace: "pre-wrap",
                        fontSize: 11,
                        maxHeight: 200,
                        overflow: "auto",
                        marginTop: 8,
                        color: t.textSecondary,
                      }}
                    >
                      {bulkLastSummary.errors_preview.map((e) => `#${e.row}: ${e.message}`).join("\n")}
                    </pre>
                  </details>
                ) : null}
              </div>
            ) : null}
            <div style={{ display: "flex", justifyContent: "flex-end" }}>
              <Button variant="ghost" type="button" disabled={bulkBusy || bulkPollTok} onClick={() => setBulkOpen(false)}>
                Close
              </Button>
            </div>
          </form>
        )}
      </Modal>
    </div>
  );
}
