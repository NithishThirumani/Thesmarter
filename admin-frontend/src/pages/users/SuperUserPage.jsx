import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as companyService from "../../company/companyService";
import * as superUserService from "../../users/superUserService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Modal from "../../components/Modal";
import Loader from "../../components/Loader";
import { formatGender, formatMarital } from "./SuperUserFormShared";

function fullName(row) {
  const f = String(row?.first_name || "").trim();
  const l = String(row?.last_name || "").trim();
  return l ? `${f} ${l}`.trim() : f || "—";
}

function rowInitials(row) {
  const f = String(row?.first_name || "").trim().charAt(0);
  const l = String(row?.last_name || "").trim().charAt(0);
  return (f + l).toUpperCase() || "—";
}

function formatCreated(iso) {
  if (iso == null || iso === "") return "—";
  const s = String(iso);
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10);
  const d = new Date(s);
  if (Number.isNaN(d.getTime())) return "—";
  return d.toISOString().slice(0, 10);
}

export default function SuperUserPage() {
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [companies, setCompanies] = useState([]);
  const [companiesLoading, setCompaniesLoading] = useState(true);
  const [companyId, setCompanyId] = useState("");

  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [listLoading, setListLoading] = useState(false);
  const [search, setSearch] = useState("");
  const [searchDebounced, setSearchDebounced] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [page, setPage] = useState(1);
  const [hoverRow, setHoverRow] = useState(null);

  const [viewRow, setViewRow] = useState(null);
  const [viewDetail, setViewDetail] = useState(null);
  const [viewLoading, setViewLoading] = useState(false);
  const [resendBusy, setResendBusy] = useState(false);

  const [filtersOpen, setFiltersOpen] = useState(false);

  const [deleteRow, setDeleteRow] = useState(null);
  const [deleteBusy, setDeleteBusy] = useState(false);

  const [resetConfirm, setResetConfirm] = useState(null);
  const [resetBusy, setResetBusy] = useState(false);

  /** Which row's ⋮ actions menu is open (row composite key). */
  const [actionsMenuKey, setActionsMenuKey] = useState(null);

  const selectedCompany = useMemo(
    () => companies.find((c) => String(c.company_id) === String(companyId)),
    [companies, companyId]
  );

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounced(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [companyId, searchDebounced, statusFilter]);

  useEffect(() => {
    if (!accessToken) return;
    let cancelled = false;
    (async () => {
      setCompaniesLoading(true);
      try {
        const res = await companyService.listCompanies({ page: 1, per_page: 100, search: undefined }, accessToken);
        if (!cancelled) setCompanies(res.data || []);
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Companies", e.message);
      } finally {
        if (!cancelled) setCompaniesLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [accessToken, addToastSafe]);

  const fetchSuperUsers = useCallback(async () => {
    if (!accessToken) {
      setList([]);
      return;
    }
    setListLoading(true);
    try {
      const res = await superUserService.listAllSuperUsers(
        {
          company_id: companyId || undefined,
          search: searchDebounced || undefined,
          status: statusFilter,
          page,
          per_page: 15,
        },
        accessToken
      );
      setList(res.data || []);
      setMeta(res.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
    } catch (e) {
      addToastSafe("error", "Super users", e.message);
      setList([]);
    } finally {
      setListLoading(false);
    }
  }, [accessToken, companyId, searchDebounced, statusFilter, page, addToastSafe]);

  useEffect(() => {
    fetchSuperUsers();
  }, [fetchSuperUsers]);

  useEffect(() => {
    if (!viewRow || !accessToken) {
      setViewDetail(null);
      return;
    }
    const cid = viewRow.company_id;
    if (!cid) {
      setViewDetail(null);
      return;
    }
    let cancelled = false;
    (async () => {
      setViewLoading(true);
      try {
        const res = await superUserService.getSuperUser(String(cid), viewRow.user_id, accessToken);
        if (!cancelled) setViewDetail(res?.data ?? res);
      } catch {
        if (!cancelled) setViewDetail(viewRow);
      } finally {
        if (!cancelled) setViewLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [viewRow, accessToken]);

  useEffect(() => {
    if (!actionsMenuKey) return;
    const onMouseDown = (e) => {
      if (e.target.closest?.("[data-super-user-row-actions]")) return;
      setActionsMenuKey(null);
    };
    document.addEventListener("mousedown", onMouseDown);
    return () => document.removeEventListener("mousedown", onMouseDown);
  }, [actionsMenuKey]);

  const companyOptions = useMemo(() => {
    const opts = [{ value: "", label: companiesLoading ? "Loading companies…" : "Select a company…" }];
    companies.forEach((c) => {
      opts.push({
        value: String(c.company_id),
        label: `${c.company_name || "Company"}${c.company_code ? ` · ${c.company_code}` : ""}`,
      });
    });
    return opts;
  }, [companies, companiesLoading]);

  const handleCreateClick = () => {
    if (companyId) {
      navigate(`/admin/companies/${companyId}/create-super-user`);
      return;
    }
    navigate(`/admin/create-super-user`);
  };

  const goEdit = (row) => {
    const cid = row?.company_id ?? companyId;
    if (!cid) return;
    navigate(`/admin/companies/${cid}/edit-super-user/${row.user_id}`);
  };

  const resetPinFromViewModal = async () => {
    const id = viewDetail?.user_id ?? viewRow?.user_id;
    const cid = viewDetail?.company_id ?? viewRow?.company_id ?? companyId;
    if (!id || !cid || !accessToken) return;
    setResendBusy(true);
    try {
      await superUserService.resetSuperUserPin(String(cid), id, accessToken);
      addToastSafe("success", "PIN reset", "A new PIN has been generated and emailed to this super user.");
    } catch (e) {
      addToastSafe("error", "Reset failed", e.message);
    } finally {
      setResendBusy(false);
    }
  };

  const applyResetPin = async () => {
    if (!accessToken || !resetConfirm) return;
    const cid = resetConfirm.company_id;
    if (!cid) return;
    setResetBusy(true);
    try {
      await superUserService.resetSuperUserPin(String(cid), resetConfirm.user_id, accessToken);
      addToastSafe("success", "PIN reset", "A new PIN has been generated and emailed.");
      setResetConfirm(null);
      await fetchSuperUsers();
    } catch (e) {
      addToastSafe("error", "Reset failed", e.message);
    } finally {
      setResetBusy(false);
    }
  };

  const confirmDelete = async () => {
    if (!deleteRow || !accessToken) return;
    const cid = deleteRow.company_id;
    if (!cid) return;
    setDeleteBusy(true);
    try {
      await superUserService.deleteSuperUser(String(cid), deleteRow.user_id, accessToken);
      addToastSafe("success", "Removed", "Super user access removed for this company.");
      setDeleteRow(null);
      await fetchSuperUsers();
    } catch (e) {
      addToastSafe("error", "Remove failed", e.message);
    } finally {
      setDeleteBusy(false);
    }
  };

  const statusBadge = (row) => {
    const on = Number(row.status) === 1;
    return <Badge status={on ? "Active" : "Inactive"} />;
  };

  const borderSoft = t.borderStrong || t.border;
  const panelRadius = 14;
  const accentLine = `linear-gradient(90deg, ${t.accent} 0%, ${t.accent}22 40%, transparent 100%)`;

  const tableCardTitle = "Super users";

  const activeFilterHint = useMemo(() => {
    const parts = [];
    if (companyId && selectedCompany?.company_name) parts.push(selectedCompany.company_name);
    else if (!companyId) parts.push("All companies");
    if (statusFilter !== "all") parts.push(statusFilter === "active" ? "Active only" : "Inactive only");
    return parts.join(" · ");
  }, [companyId, selectedCompany, statusFilter]);

  return (
    <div style={{ background: "transparent", minHeight: "100%", paddingBottom: 40 }}>
      <PageHeader
        title="Super Users"
        subtitle="Browse every super user with company context. Use Filters to narrow by tenant, keyword, or status."
        breadcrumb="Management"
        action={
          <Button variant="primary" size="md" icon="＋" onClick={handleCreateClick}>
            Create super user
          </Button>
        }
      />

      <div style={{ display: "grid", gap: 18, maxWidth: 1380 }}>
        {/* Filter drawer (enterprise): opens from right */}
        <div
          style={{
            borderRadius: panelRadius,
            border: `1px solid ${t.border}`,
            background: t.bgCard,
            boxShadow: "0 18px 48px rgba(0,0,0,0.35)",
            overflow: "hidden",
            minHeight: 280,
          }}
        >
          <div style={{ height: 3, background: accentLine }} aria-hidden />
          <div
            style={{
              padding: "16px 20px",
              borderBottom: `1px solid ${t.border}`,
              display: "flex",
              alignItems: "flex-start",
              justifyContent: "space-between",
              gap: 16,
              flexWrap: "wrap",
              background: t.bgElevated,
            }}
          >
            <div style={{ flex: "1 1 200px", minWidth: 0 }}>
              <span style={{ fontFamily: TYPE.fontDisplay, fontWeight: TYPE.black, fontSize: TYPE.lg, color: t.text, letterSpacing: "-0.02em" }}>
                {tableCardTitle}
              </span>
              <div style={{ marginTop: 6, fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontBody, lineHeight: 1.5 }}>{activeFilterHint}</div>
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap" }}>
              {!listLoading && (
                <span style={{ fontSize: TYPE.sm, color: t.textMuted, fontFamily: TYPE.fontBody }}>
                  {meta.total === 0 ? "No results" : `${meta.total} result${meta.total === 1 ? "" : "s"}`}
                </span>
              )}
              <Button variant="secondary" size="sm" onClick={() => setFiltersOpen(true)}>
                Filters
              </Button>
            </div>
          </div>

          <div style={{ padding: !listLoading && list.length === 0 ? 0 : 18 }}>
            {listLoading ? (
              <div style={{ padding: "32px 20px" }}>
                <Loader />
              </div>
            ) : (
              <>
                <div style={{ overflowX: "auto" }}>
                  <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>
                    <thead>
                      <tr>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg, width: 56 }}>Photo</th>
                        <th
                          style={{
                            textAlign: "left",
                            padding: "12px 14px",
                            color: t.accentDim || t.textMuted,
                            fontSize: 11,
                            fontWeight: TYPE.bold,
                            letterSpacing: "0.08em",
                            textTransform: "uppercase",
                            borderBottom: `2px solid ${t.borderStrong}`,
                            background: t.bg,
                            minWidth: 160,
                          }}
                        >
                          Company
                        </th>
                        <th
                          style={{
                            textAlign: "left",
                            padding: "12px 14px",
                            color: t.accentDim || t.textMuted,
                            fontSize: 11,
                            fontWeight: TYPE.bold,
                            letterSpacing: "0.08em",
                            textTransform: "uppercase",
                            borderBottom: `2px solid ${t.borderStrong}`,
                            background: t.bg,
                          }}
                        >
                          Name
                        </th>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg }}>Mobile</th>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg }}>Email</th>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg, whiteSpace: "nowrap" }}>
                          Added
                        </th>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg }}>Status</th>
                        <th style={{ ...thStyle(t), borderBottom: `2px solid ${t.borderStrong}`, background: t.bg, width: 56, textAlign: "right" }} aria-label="Actions" />
                      </tr>
                    </thead>
                    <tbody>
                      {list.length === 0 ? (
                        <tr>
                          <td colSpan={8} style={{ padding: "40px 20px", color: t.textSecondary, textAlign: "center", lineHeight: 1.6 }}>
                            No super users match your search or filters.
                            <div style={{ marginTop: 14 }}>
                              <Button variant="primary" size="sm" icon="＋" onClick={handleCreateClick}>
                                Create super user
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ) : (
                        list.map((row) => {
                          const rowKey = `${row.company_id}-${row.user_id}-${row.mapping_id}`;
                          const rowActive = Number(row.status) === 1;
                          const menuOpen = actionsMenuKey === rowKey;
                          return (
                          <tr
                            key={rowKey}
                            onMouseEnter={() => setHoverRow(rowKey)}
                            onMouseLeave={() => setHoverRow(null)}
                            style={{
                              borderBottom: `1px solid ${borderSoft}`,
                              background: hoverRow === rowKey ? t.bgHover : "transparent",
                              transition: "background 0.12s ease",
                            }}
                          >
                            <td style={{ padding: "10px 12px", verticalAlign: "middle" }}>
                              <div
                                style={{
                                  width: 40,
                                  height: 40,
                                  borderRadius: "50%",
                                  overflow: "hidden",
                                  border: `1px solid ${t.border}`,
                                  background: t.bgElevated,
                                  display: "flex",
                                  alignItems: "center",
                                  justifyContent: "center",
                                  fontSize: 12,
                                  fontWeight: TYPE.bold,
                                  color: t.accentDim,
                                }}
                              >
                                {row.avatar_url ? (
                                  <img src={row.avatar_url} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
                                ) : (
                                  rowInitials(row)
                                )}
                              </div>
                            </td>
                            <td style={{ padding: "14px 14px", color: t.textSecondary, fontWeight: TYPE.medium, maxWidth: 220 }}>
                              <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{row.company_name || "—"}</div>
                              {row.company_code ? (
                                <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginTop: 2 }}>{row.company_code}</div>
                              ) : null}
                            </td>
                            <td style={{ padding: "14px 14px", verticalAlign: "middle" }}>
                              <button
                                type="button"
                                onClick={() => {
                                  setActionsMenuKey(null);
                                  setViewRow(row);
                                }}
                                style={{
                                  background: "none",
                                  border: "none",
                                  padding: 0,
                                  margin: 0,
                                  cursor: "pointer",
                                  color: t.text,
                                  fontWeight: TYPE.medium,
                                  fontFamily: TYPE.fontBody,
                                  fontSize: TYPE.sm,
                                  textAlign: "left",
                                  borderBottom: "1px solid transparent",
                                }}
                                onMouseEnter={(e) => {
                                  e.currentTarget.style.color = t.accent;
                                  e.currentTarget.style.borderBottomColor = t.accent;
                                }}
                                onMouseLeave={(e) => {
                                  e.currentTarget.style.color = t.text;
                                  e.currentTarget.style.borderBottomColor = "transparent";
                                }}
                              >
                                {fullName(row)}
                              </button>
                            </td>
                            <td style={{ padding: "14px 14px", color: t.textMuted, fontFamily: TYPE.fontMono, fontSize: TYPE.xs }}>{row.mobile || "—"}</td>
                            <td style={{ padding: "14px 14px", color: t.textMuted }}>{row.email || "—"}</td>
                            <td style={{ padding: "14px 14px", color: t.textMuted, fontSize: TYPE.xs, whiteSpace: "nowrap" }}>
                              {formatCreated(row.created_dtm)}
                            </td>
                            <td style={{ padding: "14px 14px" }}>{statusBadge(row)}</td>
                            <td style={{ padding: "10px 12px", verticalAlign: "middle", textAlign: "right" }}>
                              <div
                                data-super-user-row-actions
                                style={{ position: "relative", display: "inline-flex", justifyContent: "flex-end" }}
                              >
                                <button
                                  type="button"
                                  aria-label="Open row actions"
                                  aria-expanded={menuOpen}
                                  aria-haspopup="menu"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setActionsMenuKey(menuOpen ? null : rowKey);
                                  }}
                                  style={{
                                    width: 36,
                                    height: 34,
                                    borderRadius: 8,
                                    border: `1px solid ${t.border}`,
                                    background: menuOpen ? t.bgElevated : t.bgHover,
                                    color: t.textSecondary,
                                    cursor: "pointer",
                                    fontSize: 18,
                                    lineHeight: 1,
                                    display: "inline-flex",
                                    alignItems: "center",
                                    justifyContent: "center",
                                  }}
                                >
                                  ⋮
                                </button>
                                {menuOpen ? (
                                  <div
                                    role="menu"
                                    style={{
                                      position: "absolute",
                                      right: 0,
                                      top: "100%",
                                      marginTop: 6,
                                      minWidth: 160,
                                      padding: "6px 0",
                                      borderRadius: 10,
                                      border: `1px solid ${t.borderStrong}`,
                                      background: t.bgCard,
                                      boxShadow: "0 12px 40px rgba(0,0,0,0.45)",
                                      zIndex: 50,
                                    }}
                                  >
                                    <button
                                      type="button"
                                      role="menuitem"
                                      disabled={!rowActive}
                                      onClick={() => {
                                        setActionsMenuKey(null);
                                        if (rowActive) goEdit(row);
                                      }}
                                      style={{
                                        display: "block",
                                        width: "100%",
                                        padding: "10px 14px",
                                        border: "none",
                                        background: "transparent",
                                        color: rowActive ? t.text : t.textMuted,
                                        fontSize: TYPE.sm,
                                        fontFamily: TYPE.fontBody,
                                        textAlign: "left",
                                        cursor: rowActive ? "pointer" : "not-allowed",
                                      }}
                                    >
                                      Edit
                                    </button>
                                    <button
                                      type="button"
                                      role="menuitem"
                                      disabled={!rowActive}
                                      onClick={() => {
                                        setActionsMenuKey(null);
                                        if (rowActive) setResetConfirm(row);
                                      }}
                                      style={{
                                        display: "block",
                                        width: "100%",
                                        padding: "10px 14px",
                                        border: "none",
                                        background: "transparent",
                                        color: rowActive ? t.text : t.textMuted,
                                        fontSize: TYPE.sm,
                                        fontFamily: TYPE.fontBody,
                                        textAlign: "left",
                                        cursor: rowActive ? "pointer" : "not-allowed",
                                      }}
                                    >
                                      Reset PIN
                                    </button>
                                    <button
                                      type="button"
                                      role="menuitem"
                                      disabled={!rowActive}
                                      onClick={() => {
                                        setActionsMenuKey(null);
                                        if (rowActive) setDeleteRow(row);
                                      }}
                                      style={{
                                        display: "block",
                                        width: "100%",
                                        padding: "10px 14px",
                                        border: "none",
                                        background: "transparent",
                                        color: rowActive ? t.danger || "#ef4444" : t.textMuted,
                                        fontSize: TYPE.sm,
                                        fontFamily: TYPE.fontBody,
                                        textAlign: "left",
                                        cursor: rowActive ? "pointer" : "not-allowed",
                                      }}
                                    >
                                      Remove
                                    </button>
                                  </div>
                                ) : null}
                              </div>
                            </td>
                          </tr>
                          );
                        })
                      )}
                    </tbody>
                  </table>
                </div>

                {meta.total > 0 && (
                  <div
                    style={{
                      display: "flex",
                      justifyContent: "space-between",
                      alignItems: "center",
                      marginTop: 4,
                      padding: "16px 18px 6px",
                      flexWrap: "wrap",
                      gap: 12,
                      borderTop: `1px solid ${t.border}`,
                      background: t.bgElevated,
                    }}
                  >
                    <span style={{ fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontBody }}>
                      Page <strong style={{ color: t.text }}>{meta.current_page}</strong> of <strong style={{ color: t.text }}>{meta.last_page}</strong>{" "}
                      · {meta.total} total
                    </span>
                    <div style={{ display: "flex", gap: 10 }}>
                      <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                        Previous
                      </Button>
                      <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
                        Next
                      </Button>
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </div>

      {filtersOpen && (
        <>
          <button
            type="button"
            aria-label="Close filters"
            onClick={() => setFiltersOpen(false)}
            style={{
              position: "fixed",
              inset: 0,
              background: "rgba(0,0,0,0.55)",
              backdropFilter: "blur(3px)",
              zIndex: 1040,
              border: "none",
              cursor: "pointer",
            }}
          />
          <div
            role="dialog"
            aria-modal="true"
            style={{
              position: "fixed",
              top: 0,
              right: 0,
              bottom: 0,
              width: "min(420px, 100vw)",
              background: t.bgCard,
              borderLeft: `1px solid ${t.borderStrong}`,
              boxShadow: "-12px 0 40px rgba(0,0,0,0.45)",
              zIndex: 1050,
              display: "flex",
              flexDirection: "column",
              fontFamily: TYPE.fontBody,
            }}
          >
            <div
              style={{
                padding: "18px 20px",
                borderBottom: `1px solid ${t.border}`,
                background: t.bgElevated,
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                gap: 12,
              }}
            >
              <div>
                <div style={{ fontSize: 11, fontWeight: TYPE.bold, color: t.accent, letterSpacing: "0.14em", textTransform: "uppercase" }}>Filters</div>
                <div style={{ marginTop: 4, fontFamily: TYPE.fontDisplay, fontWeight: TYPE.black, fontSize: TYPE.lg, color: t.text }}>Refine list</div>
              </div>
              <button
                type="button"
                onClick={() => setFiltersOpen(false)}
                style={{
                  background: t.bgSubtle ?? t.bgHover,
                  border: `1px solid ${t.border}`,
                  color: t.textSecondary,
                  width: 36,
                  height: 36,
                  borderRadius: 8,
                  cursor: "pointer",
                  fontSize: 18,
                  lineHeight: 1,
                }}
              >
                ×
              </button>
            </div>
            <div style={{ padding: "20px 20px", flex: 1, overflowY: "auto", display: "grid", gap: 18 }}>
              <Select
                label="Company"
                value={companyId}
                onChange={(v) => setCompanyId(v)}
                options={[{ value: "", label: "All companies" }, ...companyOptions.slice(1)]}
                disabled={companiesLoading}
              />
              <Input label="Search" value={search} onChange={setSearch} placeholder="Name, mobile, email, company…" />
              <Select
                label="Status"
                value={statusFilter}
                onChange={setStatusFilter}
                options={[
                  { value: "all", label: "All" },
                  { value: "active", label: "Active" },
                  { value: "inactive", label: "Inactive" },
                ]}
              />
              <p style={{ margin: 0, fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.55 }}>
                Search matches people and company names. Leave company as &quot;All companies&quot; to see everyone.
              </p>
            </div>
            <div
              style={{
                padding: "16px 20px",
                borderTop: `1px solid ${t.border}`,
                background: t.bgElevated,
                display: "flex",
                gap: 10,
                flexWrap: "wrap",
                justifyContent: "flex-end",
              }}
            >
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  setCompanyId("");
                  setSearch("");
                  setStatusFilter("all");
                  setPage(1);
                }}
              >
                Reset
              </Button>
              <Button variant="primary" size="sm" onClick={() => setFiltersOpen(false)}>
                Done
              </Button>
            </div>
          </div>
        </>
      )}

      <Modal open={!!viewRow} onClose={() => { setViewRow(null); setViewDetail(null); }} title="Super user profile" width={620}>
        {viewRow && (
          <div style={{ fontFamily: TYPE.fontBody, color: t.text }}>
            {viewLoading ? (
              <div style={{ padding: 24, display: "flex", justifyContent: "center" }}>
                <Loader />
              </div>
            ) : (
              (() => {
                const vd = viewDetail || viewRow;
                const active = Number(vd.status) === 1;
                return (
                  <div style={{ marginTop: 4 }}>
                    <div
                      style={{
                        display: "flex",
                        gap: 20,
                        flexWrap: "wrap",
                        padding: "4px 0 16px",
                        borderBottom: `1px solid ${t.border}`,
                        marginBottom: 16,
                      }}
                    >
                      <div
                        style={{
                          width: 88,
                          height: 88,
                          borderRadius: "50%",
                          overflow: "hidden",
                          border: `2px solid ${t.borderStrong}`,
                          background: t.bgElevated,
                          flexShrink: 0,
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          fontFamily: TYPE.fontDisplay,
                          fontWeight: TYPE.black,
                          fontSize: 28,
                          color: t.accentDim,
                        }}
                      >
                        {vd.avatar_url ? (
                          <img src={vd.avatar_url} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
                        ) : (
                          rowInitials(vd)
                        )}
                      </div>
                      <div style={{ flex: "1 1 220px", minWidth: 0 }}>
                        <h3
                          style={{
                            margin: "0 0 6px",
                            fontFamily: TYPE.fontDisplay,
                            fontWeight: TYPE.black,
                            fontSize: TYPE.xl,
                            color: t.text,
                            letterSpacing: "-0.02em",
                          }}
                        >
                          {fullName(vd)}
                        </h3>
                        <div style={{ fontSize: TYPE.sm, color: t.textMuted, marginBottom: 8 }}>
                          {vd.company_name || selectedCompany?.company_name || "Company"} · User #{vd.user_id}
                        </div>
                        <Badge status={active ? "Active" : "Inactive"} />
                      </div>
                    </div>

                    <dl
                      style={{
                        margin: 0,
                        display: "grid",
                        gridTemplateColumns: "repeat(auto-fill, minmax(200px, 1fr))",
                        gap: "12px 20px",
                        fontSize: TYPE.sm,
                      }}
                    >
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>
                          Mobile
                        </dt>
                        <dd style={{ margin: 0, color: t.textSecondary, fontFamily: TYPE.fontMono }}>{vd.mobile || "—"}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>
                          Email
                        </dt>
                        <dd style={{ margin: 0, color: t.textSecondary, wordBreak: "break-word" }}>{vd.email || "—"}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>Gender</dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>{formatGender(vd.gender)}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>
                          Date of birth
                        </dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>{vd.date_of_birth ? String(vd.date_of_birth).slice(0, 10) : "—"}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>
                          Marital status
                        </dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>{formatMarital(vd.marital_status)}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>Role</dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>{vd.user_type_label || "—"}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>
                          HQ branches
                        </dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>
                          {vd.branch_count != null ? vd.branch_count : "—"}
                        </dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>Mapping</dt>
                        <dd style={{ margin: 0, color: t.textMuted }}>#{vd.mapping_id}</dd>
                      </div>
                      <div>
                        <dt style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 4 }}>Added</dt>
                        <dd style={{ margin: 0, color: t.textSecondary }}>{formatCreated(vd.created_dtm)}</dd>
                      </div>
                    </dl>

                    {Array.isArray(vd.branches) && vd.branches.length > 0 ? (
                      <div style={{ marginTop: 16 }}>
                        <div style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 8 }}>
                          Branch assignments
                        </div>
                        <ul style={{ margin: 0, paddingLeft: 18, color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.6 }}>
                          {vd.branches.map((b) => (
                            <li key={`${b.branch_id}-${b.start_date || ""}`}>
                              Branch #{b.branch_id}
                              {b.user_branch_status != null ? ` · status ${b.user_branch_status}` : ""}
                            </li>
                          ))}
                        </ul>
                      </div>
                    ) : null}

                    {Array.isArray(vd.modules_permissions) && vd.modules_permissions.length > 0 ? (
                      <div style={{ marginTop: 18 }}>
                        <div style={{ fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: t.accentDim, marginBottom: 8 }}>
                          Module permissions
                        </div>
                        <div style={{ maxHeight: 220, overflow: "auto", border: `1px solid ${t.border}`, borderRadius: 8, background: t.bgElevated }}>
                          <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 11, fontFamily: TYPE.fontBody }}>
                            <thead>
                              <tr style={{ background: t.bg }}>
                                <th style={{ textAlign: "left", padding: "8px 10px", color: t.textMuted }}>Module</th>
                                <th style={{ textAlign: "center", padding: "8px 4px", color: t.textMuted }}>A</th>
                                <th style={{ textAlign: "center", padding: "8px 4px", color: t.textMuted }}>R</th>
                                <th style={{ textAlign: "center", padding: "8px 4px", color: t.textMuted }}>C</th>
                                <th style={{ textAlign: "center", padding: "8px 4px", color: t.textMuted }}>U</th>
                                <th style={{ textAlign: "center", padding: "8px 4px", color: t.textMuted }}>D</th>
                              </tr>
                            </thead>
                            <tbody>
                              {vd.modules_permissions.map((mp) => (
                                <tr key={mp.module_id} style={{ borderTop: `1px solid ${t.border}` }}>
                                  <td style={{ padding: "6px 10px", color: t.text }}>{mp.module_name || mp.module_id}</td>
                                  {["Access_priv", "Read_priv", "Create_priv", "Update_priv", "Delete_priv"].map((col) => (
                                    <td key={col} style={{ textAlign: "center", padding: "6px 4px", color: t.textMuted }}>
                                      {mp[col] === "Y" ? "Y" : "—"}
                                    </td>
                                  ))}
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      </div>
                    ) : null}

                    {active && (
                      <div
                        style={{
                          marginTop: 22,
                          paddingTop: 18,
                          borderTop: `1px solid ${t.border}`,
                          display: "flex",
                          flexWrap: "wrap",
                          gap: 10,
                          alignItems: "center",
                        }}
                      >
                        <span style={{ fontSize: TYPE.xs, color: t.textMuted, flex: "1 1 100%" }}>
                          <strong style={{ color: t.textSecondary }}>Reset PIN</strong> generates a new PIN for {vd.email || "this user"}. The previous
                          PIN stops working immediately; the new PIN is sent by email.
                        </span>
                        <Button variant="primary" size="sm" disabled={resendBusy} onClick={resetPinFromViewModal}>
                          {resendBusy ? "Resetting…" : "Reset PIN"}
                        </Button>
                      </div>
                    )}
                  </div>
                );
              })()
            )}
          </div>
        )}
      </Modal>

      <Modal open={!!resetConfirm} onClose={() => !resetBusy && setResetConfirm(null)} title="Reset PIN?" width={420}>
        {resetConfirm && (
          <>
            <p style={{ margin: "0 0 16px", fontFamily: TYPE.fontBody, lineHeight: 1.55, color: t.textSecondary }}>
              A new PIN will be generated for{" "}
              <strong style={{ color: t.text }}>{resetConfirm.email || fullName(resetConfirm)}</strong>. The old PIN stops working immediately. The
              new PIN will be emailed to the address on file.
            </p>
            <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
              <Button variant="ghost" disabled={resetBusy} onClick={() => setResetConfirm(null)}>
                Cancel
              </Button>
              <Button variant="primary" disabled={resetBusy} onClick={applyResetPin}>
                {resetBusy ? "Resetting…" : "Reset PIN"}
              </Button>
            </div>
          </>
        )}
      </Modal>

      <Modal open={!!deleteRow} onClose={() => !deleteBusy && setDeleteRow(null)} title="Remove super user?" width={440}>
        {deleteRow && (
          <>
            <p style={{ margin: "0 0 16px", fontFamily: TYPE.fontBody, lineHeight: 1.55, color: t.textSecondary }}>
              Remove super user access for <strong style={{ color: t.text }}>{fullName(deleteRow)}</strong>? Their login account remains; only this
              company link is deactivated.
            </p>
            <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
              <Button variant="ghost" disabled={deleteBusy} onClick={() => setDeleteRow(null)}>
                Cancel
              </Button>
              <Button variant="danger" disabled={deleteBusy} onClick={confirmDelete}>
                {deleteBusy ? "Removing…" : "Remove access"}
              </Button>
            </div>
          </>
        )}
      </Modal>
    </div>
  );
}

function thStyle(t) {
  return {
    textAlign: "left",
    padding: "12px 14px",
    color: t.accentDim || t.textMuted,
    fontSize: 11,
    fontWeight: TYPE.bold,
    letterSpacing: "0.08em",
    textTransform: "uppercase",
  };
}
