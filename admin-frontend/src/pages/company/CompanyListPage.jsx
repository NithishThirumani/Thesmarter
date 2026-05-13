import React, { useState, useEffect, useCallback, useMemo } from "react";
import { useNavigate, useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as companyService from "../../company/companyService";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Modal from "../../components/Modal";
import TableRowSkeleton from "../../components/skeleton/TableRowSkeleton";

export default function CompanyListPage() {
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [searchDebounce, setSearchDebounce] = useState("");
  const [selectedIds, setSelectedIds] = useState([]);

  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [exportMenuOpen, setExportMenuOpen] = useState(false);
  const [filterOpen, setFilterOpen] = useState(false);
  const [statusFilter, setStatusFilter] = useState("all"); // "all" | "active" | "inactive"
  /** Server-side: newest first by default; optional name / created order */
  const [sortMode, setSortMode] = useState("created_desc");
  const [lobFilter, setLobFilter] = useState("");
  const [lobOptions, setLobOptions] = useState([]);
  const [lobLoading, setLobLoading] = useState(false);

  const fetchList = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await companyService.listCompanies(
        {
          page,
          per_page: 15,
          search: searchDebounce || undefined,
          sort: sortMode,
          status: statusFilter === "all" ? undefined : statusFilter,
          company_business_id: lobFilter || undefined,
        },
        accessToken
      );
      setList(res.data || []);
      setMeta(res.meta || {});
    } catch (e) {
      addToastSafe("error", "Error", e.message);
      setList([]);
    } finally {
      setLoading(false);
    }
  }, [accessToken, page, searchDebounce, sortMode, statusFilter, lobFilter]);

  useEffect(() => {
    fetchList();
  }, [fetchList]);

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounce(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [statusFilter, sortMode, lobFilter, searchDebounce]);

  const loadLobs = useCallback(async () => {
    if (!accessToken) return;
    setLobLoading(true);
    try {
      const res = await lobService.getLineOfBusinessDropdowns(accessToken);
      setLobOptions(res.data || []);
    } catch (e) {
      addToastSafe("error", "Line of business", e.message);
      setLobOptions([]);
    } finally {
      setLobLoading(false);
    }
  }, [accessToken, addToastSafe]);

  useEffect(() => {
    loadLobs();
  }, [loadLobs]);

  const currentPageIds = useMemo(() => list.map((r) => r.company_id), [list]);
  const allSelectedOnPage = currentPageIds.length > 0 && currentPageIds.every((id) => selectedIds.includes(id));

  const toggleSelectAllOnPage = () => {
    if (allSelectedOnPage) {
      setSelectedIds((prev) => prev.filter((id) => !currentPageIds.includes(id)));
      return;
    }
    setSelectedIds((prev) => Array.from(new Set([...prev, ...currentPageIds])));
  };

  const statusBadge = (companyStatus) => {
    const isActive =
      companyStatus === "A" ||
      companyStatus === "Active" ||
      companyStatus === 1 ||
      companyStatus === "1" ||
      companyStatus === true;
    return isActive ? "Active" : "Inactive";
  };

  const renderLogo = (row) => {
    const logo = row.company_logo_urls?.sm || row.company_logo_urls?.md || row.company_logo_urls?.lg;
    if (logo) {
      return (
        <img
          src={logo}
          alt={row.company_name}
          style={{
            width: 32,
            height: 32,
            borderRadius: "50%",
            objectFit: "cover",
            border: `1px solid ${t.borderStrong}`,
            background: t.bgSubtle,
          }}
        />
      );
    }
    const initial = (row.company_name || "C").trim()[0]?.toUpperCase?.() || "C";
    return (
      <div
        style={{
          width: 32,
          height: 32,
          borderRadius: "50%",
          background: t.accentSubtle,
          border: `1px solid ${t.accent}60`,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          fontFamily: TYPE.fontMono,
          fontWeight: TYPE.bold,
          color: t.accent,
          flexShrink: 0,
        }}
      >
        {initial}
      </div>
    );
  };

  const handleDeleteClick = (row, e) => {
    e?.stopPropagation?.();
    setDeleteTarget(row);
    setDeleteModalOpen(true);
  };

  const confirmDelete = async () => {
    if (!deleteTarget || !accessToken) return;
    setDeleting(true);
    try {
      await companyService.deleteCompany(String(deleteTarget.company_id), accessToken);
      addToastSafe("success", "Deleted", `${deleteTarget.company_name} has been deleted.`);
      setDeleteModalOpen(false);
      setDeleteTarget(null);
      setSelectedIds((prev) => prev.filter((id) => id !== deleteTarget.company_id));
      // Refresh current page
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleting(false);
    }
  };

  const normalizeStatus = (companyStatus) => {
    if (
      companyStatus === "A" ||
      companyStatus === "Active" ||
      companyStatus === 1 ||
      companyStatus === "1" ||
      companyStatus === true
    ) {
      return "Active";
    }
    return "Inactive";
  };

  const escapeHtml = (value) => {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  };

  const exportCSV = () => {
    const headers = ["company_id", "company_code", "company_name", "company_status"];
    const rows = list.map((r) => [
      r.company_id ?? "",
      r.company_code ?? "",
      r.company_name ?? "",
      normalizeStatus(r.company_status),
    ]);

    const toCSVCell = (cell) => {
      const v = String(cell ?? "");
      const needsQuotes = /[",\n]/.test(v);
      const escaped = v.replace(/"/g, '""');
      return needsQuotes ? `"${escaped}"` : escaped;
    };

    const csv = [
      headers.join(","),
      ...rows.map((row) => row.map(toCSVCell).join(",")),
    ].join("\n");

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "companies.csv";
    a.click();
    URL.revokeObjectURL(url);
    setExportMenuOpen(false);
  };

  const exportExcel = () => {
    // Excel can open HTML tables with .xls extension.
    const headers = ["Company ID", "Company Code", "Company Name", "Status"];
    const rows = list.map((r) => [
      r.company_id ?? "",
      r.company_code ?? "",
      r.company_name ?? "",
      normalizeStatus(r.company_status),
    ]);

    const tableHead = `<tr>${headers.map((h) => `<th style="padding:6px 10px;border:1px solid #ddd;background:#111;color:#fff;font-family:Arial, sans-serif;font-size:12px;">${escapeHtml(h)}</th>`).join("")}</tr>`;
    const tableBody = rows
      .map(
        (row) =>
          `<tr>${row
            .map((c) => `<td style="padding:6px 10px;border:1px solid #ddd;font-family:Arial, sans-serif;font-size:12px;">${escapeHtml(c)}</td>`)
            .join("")}</tr>`
      )
      .join("");

    const html = `
      <html>
        <head><meta charset="utf-8" /></head>
        <body>
          <table cellspacing="0" cellpadding="0">${tableHead}${tableBody}</table>
        </body>
      </html>
    `;

    const blob = new Blob([html], { type: "application/vnd.ms-excel;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "companies.xls";
    a.click();
    URL.revokeObjectURL(url);
    setExportMenuOpen(false);
  };

  return (
    <div>
      <PageHeader
        title="Companies"
        subtitle="Create and manage tenant companies"
        breadcrumb="Companies"
        action={
          <Button variant="primary" icon="+" onClick={() => navigate("/company/new")}>
            New Company
          </Button>
        }
      />
      <Card>
        <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 16, flexWrap: "wrap" }}>
          <div style={{ flex: 1, minWidth: 260 }}>
            <Input
              placeholder="Search by name or code…"
              value={search}
              onChange={setSearch}
            />
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
            <div style={{ position: "relative" }}>
              <Button
                variant="ghost"
                size="sm"
                icon="↓"
                onClick={(e) => {
                  e.stopPropagation();
                  setExportMenuOpen((v) => !v);
                }}
              >
                Export
              </Button>
              {exportMenuOpen && (
                <div
                  style={{
                    position: "absolute",
                    right: 0,
                    top: 44,
                    width: 220,
                    background: t.bgCard,
                    border: `1px solid ${t.borderStrong}`,
                    borderRadius: 10,
                    boxShadow: "0 20px 60px rgba(0,0,0,0.7)",
                    zIndex: 100,
                    overflow: "hidden",
                  }}
                  onClick={(e) => e.stopPropagation()}
                >
                  <button
                    type="button"
                    onClick={exportCSV}
                    style={{
                      width: "100%",
                      background: "transparent",
                      border: "none",
                      padding: "12px 14px",
                      cursor: "pointer",
                      color: t.text,
                      fontFamily: TYPE.fontBody,
                      fontSize: TYPE.sm,
                      textAlign: "left",
                    }}
                  >
                    Export CSV
                  </button>
                  <div style={{ height: 1, background: t.border }} />
                  <button
                    type="button"
                    onClick={exportExcel}
                    style={{
                      width: "100%",
                      background: "transparent",
                      border: "none",
                      padding: "12px 14px",
                      cursor: "pointer",
                      color: t.text,
                      fontFamily: TYPE.fontBody,
                      fontSize: TYPE.sm,
                      textAlign: "left",
                    }}
                  >
                    Export Excel
                  </button>
                </div>
              )}
            </div>

            <div style={{ position: "relative" }}>
              <Button
                variant="ghost"
                size="sm"
                icon="⚙"
                onClick={(e) => {
                  e.stopPropagation();
                  setFilterOpen((v) => !v);
                  setExportMenuOpen(false);
                }}
              >
                Filter
              </Button>
              {filterOpen && (
                <div
                  style={{
                    position: "absolute",
                    right: 0,
                    top: 44,
                    width: 300,
                    background: t.bgCard,
                    border: `1px solid ${t.borderStrong}`,
                    borderRadius: 10,
                    boxShadow: "0 20px 60px rgba(0,0,0,0.7)",
                    zIndex: 100,
                    overflow: "hidden",
                  }}
                  onClick={(e) => e.stopPropagation()}
                >
                  <div style={{ padding: "12px 14px", borderBottom: `1px solid ${t.border}` }}>
                    <div style={{ color: t.textSecondary, fontFamily: TYPE.fontBody, fontWeight: TYPE.semibold, fontSize: TYPE.sm }}>
                      Filters
                    </div>
                  </div>
                  <div style={{ padding: "12px 14px", display: "flex", flexDirection: "column", gap: 12 }}>
                    <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                      <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontWeight: TYPE.bold, textTransform: "uppercase", letterSpacing: "0.07em" }}>
                        Line of business
                      </div>
                      {lobLoading ? (
                        <div style={{ fontSize: TYPE.sm, color: t.textMuted }}>Loading…</div>
                      ) : (
                        <Select
                          label=""
                          value={lobFilter}
                          onChange={setLobFilter}
                          options={[
                            { value: "", label: "All lines of business" },
                            ...(lobOptions || []).map((o) => ({
                              value: String(o.lob_id ?? o.id ?? ""),
                              label: o.lob_name || String(o.lob_id ?? ""),
                            })),
                          ]}
                        />
                      )}
                    </div>

                    <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                      <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontWeight: TYPE.bold, textTransform: "uppercase", letterSpacing: "0.07em" }}>
                        Status
                      </div>
                      <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                        {[
                          { id: "all", label: "All" },
                          { id: "active", label: "Active" },
                          { id: "inactive", label: "Inactive" },
                        ].map((opt) => (
                          <label key={opt.id} style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
                            <input
                              type="radio"
                              name="company-status"
                              checked={statusFilter === opt.id}
                              onChange={() => setStatusFilter(opt.id)}
                              style={{ accentColor: t.accent }}
                            />
                            <span style={{ color: t.text, fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>{opt.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>

                    <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                      <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontWeight: TYPE.bold, textTransform: "uppercase", letterSpacing: "0.07em" }}>
                        Sort order
                      </div>
                      <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                        {[
                          { id: "created_desc", label: "Newest created first" },
                          { id: "created_asc", label: "Oldest created first" },
                          { id: "name_asc", label: "Company name A–Z" },
                          { id: "name_desc", label: "Company name Z–A" },
                        ].map((opt) => (
                          <label key={opt.id} style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
                            <input
                              type="radio"
                              name="company-sort"
                              checked={sortMode === opt.id}
                              onChange={() => setSortMode(opt.id)}
                              style={{ accentColor: t.accent }}
                            />
                            <span style={{ color: t.text, fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>{opt.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>

                    <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", marginTop: 6 }}>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          setStatusFilter("all");
                          setSortMode("created_desc");
                          setLobFilter("");
                        }}
                      >
                        Reset
                      </Button>
                      <Button variant="primary" size="sm" onClick={() => setFilterOpen(false)}>
                        Apply
                      </Button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
        {selectedIds.length > 0 && (
          <div style={{ marginBottom: 12, display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12 }}>
            <div style={{ color: t.accent, fontFamily: TYPE.fontBody, fontSize: TYPE.sm, fontWeight: TYPE.semibold }}>
              {selectedIds.length} selected
            </div>
            <Button variant="ghost" size="sm" onClick={() => setSelectedIds([])}>
              Clear
            </Button>
          </div>
        )}

        {loading ? (
          <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody }}>
            <thead>
              <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                <th style={{ padding: "12px 16px", width: 44 }}>
                  <input type="checkbox" checked={false} disabled style={{ accentColor: t.accent, width: 14, height: 14 }} />
                </th>
                <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>
                  Logo
                </th>
                <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>
                  Code
                </th>
                <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>
                  Company
                </th>
                <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>
                  Status
                </th>
                <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em", textAlign: "right" }}>
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>{Array.from({ length: 6 }).map((_, i) => <TableRowSkeleton key={i} />)}</tbody>
          </table>
        ) : list.length === 0 ? (
          <div style={{ padding: 48, textAlign: "center", color: t.textMuted, fontFamily: TYPE.fontBody }}>
            <div style={{ fontSize: 32, marginBottom: 8 }}>🏢</div>
            <div style={{ fontWeight: TYPE.semibold, color: t.textSecondary, marginBottom: 4 }}>No companies</div>
            <div style={{ fontSize: TYPE.sm }}>Create your first company with the wizard.</div>
            <Button variant="primary" style={{ marginTop: 16 }} onClick={() => navigate("/company/new")}>
              New Company
            </Button>
          </div>
        ) : (
          <>
            <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                  <th style={{ padding: "12px 16px", width: 44 }}>
                    <input
                      type="checkbox"
                      checked={allSelectedOnPage}
                      onChange={toggleSelectAllOnPage}
                      style={{ accentColor: t.accent, width: 14, height: 14 }}
                      aria-label="Select all companies on this page"
                      onClick={(e) => e.stopPropagation()}
                    />
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>Logo</th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>Code</th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>Company</th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em" }}>Status</th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em", textAlign: "right" }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {list.map((row) => (
                  <tr
                    key={row.company_id}
                    onClick={() => navigate(`/company/${row.company_id}`)}
                    style={{
                      borderBottom: `1px solid ${t.border}`,
                      cursor: "pointer",
                      background: "transparent",
                    }}
                    onMouseEnter={(e) => { e.currentTarget.style.background = t.bgHover; }}
                    onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
                  >
                    <td style={{ padding: "14px 16px", width: 44 }}>
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(row.company_id)}
                        onChange={() => {
                          setSelectedIds((prev) =>
                            prev.includes(row.company_id) ? prev.filter((id) => id !== row.company_id) : [...prev, row.company_id]
                          );
                        }}
                        style={{ accentColor: t.accent, width: 14, height: 14 }}
                        onClick={(e) => e.stopPropagation()}
                      />
                    </td>
                    <td style={{ padding: "14px 16px", width: 54 }}>{renderLogo(row)}</td>
                    <td style={{ padding: "14px 16px", color: t.textMuted, fontFamily: TYPE.fontMono, fontSize: TYPE.sm }}>{row.company_code || "—"}</td>
                    <td style={{ padding: "14px 16px", color: t.text, fontWeight: TYPE.semibold }}>{row.company_name}</td>
                    <td style={{ padding: "14px 16px" }}>
                      <Badge status={statusBadge(row.company_status)} />
                    </td>
                    <td style={{ padding: "14px 16px", textAlign: "right" }}>
                      <div style={{ display: "flex", gap: 6, justifyContent: "flex-end" }}>
                        <Button
                          variant="ghost"
                          size="sm"
                          icon="👁"
                          onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/company/${row.company_id}`);
                          }}
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon="✎"
                          onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/company/${row.company_id}/edit`);
                          }}
                        />
                        <Button
                          variant="danger"
                          size="sm"
                          icon="🗑"
                          onClick={(e) => handleDeleteClick(row, e)}
                        />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {meta.last_page > 1 && (
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "12px 16px", borderTop: `1px solid ${t.border}` }}>
                <span style={{ fontSize: TYPE.sm, color: t.textMuted }}>Page {meta.current_page} of {meta.last_page} ({meta.total} total)</span>
                <div style={{ display: "flex", gap: 8 }}>
                  <Button variant="ghost" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>Previous</Button>
                  <Button variant="ghost" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Next</Button>
                </div>
              </div>
            )}
          </>
        )}
      </Card>

      <Modal
        open={deleteModalOpen}
        onClose={() => {
          if (!deleting) {
            setDeleteModalOpen(false);
            setDeleteTarget(null);
          }
        }}
        title="Delete company?"
        width={460}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          <div style={{ color: t.textSecondary, fontFamily: TYPE.fontBody, fontSize: TYPE.sm, lineHeight: 1.5 }}>
            This will permanently delete the company record.
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button
              variant="ghost"
              onClick={() => {
                if (!deleting) {
                  setDeleteModalOpen(false);
                  setDeleteTarget(null);
                }
              }}
              disabled={deleting}
            >
              Cancel
            </Button>
            <Button variant="danger" onClick={confirmDelete} disabled={deleting}>
              {deleting ? "Deleting…" : "Delete"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
