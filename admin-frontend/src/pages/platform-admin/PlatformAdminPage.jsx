import React, { useState, useEffect, useCallback, useMemo } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as platformAdminService from "../../platformAdmin/platformAdminService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Modal from "../../components/Modal";
import Badge from "../../components/Badge";
import TextSkeleton from "../../components/skeleton/TextSkeleton";

const PHONE_RE = /^\+?[0-9\s\-()]{7,20}$/;

export default function PlatformAdminPage() {
  const { accessToken, user } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [searchDebounce, setSearchDebounce] = useState("");
  const [page, setPage] = useState(1);
  const [sortMode, setSortMode] = useState("created_desc");
  const [statusFilter, setStatusFilter] = useState("all");
  const [filterOpen, setFilterOpen] = useState(false);

  const [createOpen, setCreateOpen] = useState(false);
  const [createSubmitting, setCreateSubmitting] = useState(false);
  const [createForm, setCreateForm] = useState({ name: "", email: "", phone_number: "" });

  const [editOpen, setEditOpen] = useState(false);
  const [editSubmitting, setEditSubmitting] = useState(false);
  const [editRow, setEditRow] = useState(null);
  const [editForm, setEditForm] = useState({ name: "", email: "", phone_number: "", is_active: true });

  const [statusConfirm, setStatusConfirm] = useState(null);
  const [statusBusy, setStatusBusy] = useState(false);

  const [resetConfirm, setResetConfirm] = useState(null);
  const [resetBusy, setResetBusy] = useState(false);

  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [deleteBusy, setDeleteBusy] = useState(false);

  const [actionsMenuKey, setActionsMenuKey] = useState(null);

  const fetchList = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await platformAdminService.listPlatformAdmins(
        {
          page,
          per_page: 15,
          search: searchDebounce || undefined,
          sort: sortMode,
          status: statusFilter,
        },
        accessToken
      );
      setList(res.data || []);
      setMeta(res.meta || {});
    } catch (e) {
      addToastSafe("error", "Error", e.message || "Failed to load super admins.");
      setList([]);
    } finally {
      setLoading(false);
    }
  }, [accessToken, page, searchDebounce, sortMode, statusFilter, addToastSafe]);

  useEffect(() => {
    fetchList();
  }, [fetchList]);

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounce(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [searchDebounce, sortMode, statusFilter]);

  useEffect(() => {
    if (!actionsMenuKey) return;
    const onMouseDown = (e) => {
      if (e.target.closest?.("[data-platform-admin-row-actions]")) return;
      setActionsMenuKey(null);
    };
    document.addEventListener("mousedown", onMouseDown);
    return () => document.removeEventListener("mousedown", onMouseDown);
  }, [actionsMenuKey]);

  const createPhoneOk = useMemo(() => {
    const p = String(createForm.phone_number || "").trim();
    return p.length === 0 || PHONE_RE.test(p);
  }, [createForm.phone_number]);

  const editPhoneOk = useMemo(() => {
    const p = String(editForm.phone_number || "").trim();
    return p.length === 0 || PHONE_RE.test(p);
  }, [editForm.phone_number]);

  const openEdit = (row) => {
    setEditRow(row);
    setEditForm({
      name: row.name || "",
      email: row.email || "",
      phone_number: row.phone_number || "",
      is_active: !!row.is_active,
    });
    setEditOpen(true);
  };

  const submitCreate = async () => {
    if (!accessToken) return;
    const name = createForm.name.trim();
    const email = createForm.email.trim().toLowerCase();
    if (!name || !email) {
      addToastSafe("error", "Validation", "Name and email are required.");
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      addToastSafe("error", "Validation", "Enter a valid email.");
      return;
    }
    if (!createPhoneOk) {
      addToastSafe("error", "Validation", "Phone number format is invalid.");
      return;
    }
    setCreateSubmitting(true);
    try {
      await platformAdminService.createPlatformAdmin(
        {
          name,
          email,
          phone_number: createForm.phone_number.trim() || null,
        },
        accessToken
      );
      addToastSafe("success", "Created", "Invitation email sent with initial PIN.");
      setCreateOpen(false);
      setCreateForm({ name: "", email: "", phone_number: "" });
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Create failed", e.message);
    } finally {
      setCreateSubmitting(false);
    }
  };

  const submitEdit = async () => {
    if (!accessToken || !editRow) return;
    const name = editForm.name.trim();
    const email = editForm.email.trim().toLowerCase();
    if (!name || !email) {
      addToastSafe("error", "Validation", "Name and email are required.");
      return;
    }
    if (!editPhoneOk) {
      addToastSafe("error", "Validation", "Phone number format is invalid.");
      return;
    }
    setEditSubmitting(true);
    try {
      await platformAdminService.updatePlatformAdmin(
        editRow.id,
        {
          name,
          email,
          phone_number: editForm.phone_number.trim() || null,
          is_active: !!editForm.is_active,
        },
        accessToken
      );
      addToastSafe("success", "Saved", "Super admin updated.");
      setEditOpen(false);
      setEditRow(null);
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Update failed", e.message);
    } finally {
      setEditSubmitting(false);
    }
  };

  const applyStatusChange = async () => {
    if (!accessToken || !statusConfirm) return;
    setStatusBusy(true);
    try {
      await platformAdminService.patchPlatformAdminStatus(statusConfirm.row.id, statusConfirm.nextActive, accessToken);
      addToastSafe("success", "Updated", statusConfirm.nextActive ? "Activated." : "Deactivated.");
      setStatusConfirm(null);
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Failed", e.message);
    } finally {
      setStatusBusy(false);
    }
  };

  const applyResetPin = async () => {
    if (!accessToken || !resetConfirm) return;
    setResetBusy(true);
    try {
      const res = await platformAdminService.resetPlatformAdminPin(resetConfirm.row.id, accessToken);
      addToastSafe(
        "success",
        "PIN reset",
        res.meta?.mail_sent === false
          ? "PIN was reset but email could not be sent — check mail settings."
          : res.message || "New PIN emailed."
      );
      setResetConfirm(null);
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Reset failed", e.message);
    } finally {
      setResetBusy(false);
    }
  };

  const applyDelete = async () => {
    if (!accessToken || !deleteConfirm) return;
    setDeleteBusy(true);
    try {
      await platformAdminService.deletePlatformAdmin(deleteConfirm.row.id, accessToken);
      addToastSafe("success", "Deleted", "Super admin removed.");
      setDeleteConfirm(null);
      await fetchList();
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleteBusy(false);
    }
  };

  return (
    <div>
      <PageHeader
        title="Super admins"
        subtitle="Create and manage super admin accounts (PIN + email sign-in); invitations use platform mail settings."
        breadcrumb="Super admins"
        action={
          <Button variant="primary" icon="+" onClick={() => setCreateOpen(true)}>
            Create super admin
          </Button>
        }
      />

      <Card>
        <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 16, flexWrap: "wrap" }}>
          <div style={{ flex: 1, minWidth: 240 }}>
            <Input placeholder="Search name, email, phone…" value={search} onChange={setSearch} />
          </div>
          <div style={{ position: "relative" }}>
            <Button
              variant="ghost"
              size="sm"
              icon="⚙"
              onClick={(e) => {
                e.stopPropagation();
                setFilterOpen((v) => !v);
              }}
            >
              Filters
            </Button>
            {filterOpen && (
              <div
                style={{
                  position: "absolute",
                  right: 0,
                  top: 44,
                  width: 280,
                  background: t.bgCard,
                  border: `1px solid ${t.borderStrong}`,
                  borderRadius: 10,
                  boxShadow: "0 20px 60px rgba(0,0,0,0.45)",
                  zIndex: 100,
                  padding: "14px 16px",
                }}
                onClick={(e) => e.stopPropagation()}
              >
                <div style={{ marginBottom: 12 }}>
                  <div style={{ fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, marginBottom: 8, textTransform: "uppercase" }}>
                    Status
                  </div>
                  {[
                    { id: "all", label: "All" },
                    { id: "active", label: "Active" },
                    { id: "inactive", label: "Inactive" },
                  ].map((opt) => (
                    <label key={opt.id} style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer", marginBottom: 6 }}>
                      <input
                        type="radio"
                        name="pa-status"
                        checked={statusFilter === opt.id}
                        onChange={() => setStatusFilter(opt.id)}
                        style={{ accentColor: t.accent }}
                      />
                      <span style={{ color: t.text, fontSize: TYPE.sm }}>{opt.label}</span>
                    </label>
                  ))}
                </div>
                <div style={{ marginBottom: 12 }}>
                  <div style={{ fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, marginBottom: 8, textTransform: "uppercase" }}>
                    Sort
                  </div>
                  {[
                    { id: "created_desc", label: "Newest first" },
                    { id: "created_asc", label: "Oldest first" },
                    { id: "updated_desc", label: "Recently updated" },
                    { id: "name_asc", label: "Name A–Z" },
                    { id: "name_desc", label: "Name Z–A" },
                  ].map((opt) => (
                    <label key={opt.id} style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer", marginBottom: 6 }}>
                      <input
                        type="radio"
                        name="pa-sort"
                        checked={sortMode === opt.id}
                        onChange={() => setSortMode(opt.id)}
                        style={{ accentColor: t.accent }}
                      />
                      <span style={{ color: t.text, fontSize: TYPE.sm }}>{opt.label}</span>
                    </label>
                  ))}
                </div>
                <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setStatusFilter("all");
                      setSortMode("created_desc");
                    }}
                  >
                    Reset
                  </Button>
                  <Button variant="primary" size="sm" onClick={() => setFilterOpen(false)}>
                    Done
                  </Button>
                </div>
              </div>
            )}
          </div>
        </div>

        {loading ? (
          <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody }}>
            <thead>
              <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                <th style={{ padding: "12px 14px", fontSize: TYPE.xs, color: t.textMuted }}>Name</th>
                <th style={{ padding: "12px 14px", fontSize: TYPE.xs, color: t.textMuted }}>Email</th>
                <th style={{ padding: "12px 14px", fontSize: TYPE.xs, color: t.textMuted }}>Phone</th>
                <th style={{ padding: "12px 14px", fontSize: TYPE.xs, color: t.textMuted }}>Status</th>
                <th style={{ padding: "12px 14px", fontSize: TYPE.xs, color: t.textMuted, textAlign: "right" }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} style={{ borderBottom: `1px solid ${t.border}` }}>
                  <td style={{ padding: "14px" }}>
                    <TextSkeleton width={120} height={14} />
                  </td>
                  <td style={{ padding: "14px", minWidth: 180 }}>
                    <TextSkeleton width="85%" height={14} />
                  </td>
                  <td style={{ padding: "14px", width: 120 }}>
                    <TextSkeleton width={90} height={14} />
                  </td>
                  <td style={{ padding: "14px", width: 88 }}>
                    <TextSkeleton width={64} height={22} style={{ borderRadius: 6 }} />
                  </td>
                  <td style={{ padding: "14px", textAlign: "right", width: 56 }}>
                    <div style={{ display: "inline-flex", justifyContent: "flex-end" }}>
                      <TextSkeleton width={36} height={34} style={{ borderRadius: 8 }} />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : list.length === 0 ? (
          <div style={{ padding: 48, textAlign: "center", color: t.textMuted }}>
            No super admins yet. Create one to add another portal administrator.
          </div>
        ) : (
          <>
            <div style={{ overflowX: "auto" }}>
              <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody, minWidth: 560 }}>
                <thead>
                  <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                    <th style={{ padding: "12px 14px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                      Name
                    </th>
                    <th style={{ padding: "12px 14px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                      Email
                    </th>
                    <th style={{ padding: "12px 14px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                      Phone
                    </th>
                    <th style={{ padding: "12px 14px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                      Status
                    </th>
                    <th
                      style={{
                        padding: "12px 14px",
                        fontSize: TYPE.xs,
                        fontWeight: TYPE.bold,
                        color: t.textMuted,
                        textTransform: "uppercase",
                        textAlign: "right",
                        width: 56,
                      }}
                      aria-label="Actions"
                    />
                  </tr>
                </thead>
                <tbody>
                  {list.map((row) => {
                    const menuOpen = actionsMenuKey === row.id;
                    const canDelete = !!(user?.id && row.id !== user.id);
                    const menuItemStyle = (danger) => ({
                      display: "flex",
                      alignItems: "center",
                      gap: 10,
                      width: "100%",
                      padding: "10px 14px",
                      border: "none",
                      background: "transparent",
                      color: danger ? t.danger || "#ef4444" : t.text,
                      fontSize: TYPE.sm,
                      fontFamily: TYPE.fontBody,
                      textAlign: "left",
                      cursor: "pointer",
                    });
                    return (
                      <tr key={row.id} style={{ borderBottom: `1px solid ${t.border}` }}>
                        <td style={{ padding: "14px", fontWeight: TYPE.semibold, color: t.text }}>{row.name}</td>
                        <td style={{ padding: "14px", color: t.textSecondary }}>{row.email}</td>
                        <td style={{ padding: "14px", color: t.textMuted }}>{row.phone_number || "—"}</td>
                        <td style={{ padding: "14px" }}>
                          <Badge status={row.is_active ? "Active" : "Inactive"} />
                        </td>
                        <td style={{ padding: "10px 14px", verticalAlign: "middle", textAlign: "right" }}>
                          <div
                            data-platform-admin-row-actions
                            style={{ position: "relative", display: "inline-flex", justifyContent: "flex-end" }}
                          >
                            <button
                              type="button"
                              aria-label="Open row actions"
                              aria-expanded={menuOpen}
                              aria-haspopup="menu"
                              onClick={(e) => {
                                e.stopPropagation();
                                setActionsMenuKey(menuOpen ? null : row.id);
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
                                  minWidth: 200,
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
                                  onClick={() => {
                                    setActionsMenuKey(null);
                                    openEdit(row);
                                  }}
                                  style={menuItemStyle(false)}
                                >
                                  <span style={{ width: 22, display: "inline-flex", justifyContent: "center", fontSize: 15 }} aria-hidden>
                                    ✎
                                  </span>
                                  <span>Edit</span>
                                </button>
                                <button
                                  type="button"
                                  role="menuitem"
                                  onClick={() => {
                                    setActionsMenuKey(null);
                                    setStatusConfirm({ row, nextActive: !row.is_active });
                                  }}
                                  style={menuItemStyle(false)}
                                >
                                  <span style={{ width: 22, display: "inline-flex", justifyContent: "center", fontSize: 14 }} aria-hidden>
                                    {row.is_active ? "⏸" : "▶"}
                                  </span>
                                  <span>{row.is_active ? "Deactivate" : "Activate"}</span>
                                </button>
                                <button
                                  type="button"
                                  role="menuitem"
                                  onClick={() => {
                                    setActionsMenuKey(null);
                                    setResetConfirm({ row });
                                  }}
                                  style={menuItemStyle(false)}
                                >
                                  <span style={{ width: 22, display: "inline-flex", justifyContent: "center", fontSize: 15 }} aria-hidden>
                                    ↻
                                  </span>
                                  <span>Reset PIN</span>
                                </button>
                                {canDelete ? (
                                  <button
                                    type="button"
                                    role="menuitem"
                                    onClick={() => {
                                      setActionsMenuKey(null);
                                      setDeleteConfirm({ row });
                                    }}
                                    style={menuItemStyle(true)}
                                  >
                                    <span style={{ width: 22, display: "inline-flex", justifyContent: "center", fontSize: 15 }} aria-hidden>
                                      🗑
                                    </span>
                                    <span>Delete</span>
                                  </button>
                                ) : null}
                              </div>
                            ) : null}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            {meta.last_page > 1 && (
              <div
                style={{
                  display: "flex",
                  justifyContent: "space-between",
                  alignItems: "center",
                  padding: "12px 16px",
                  borderTop: `1px solid ${t.border}`,
                }}
              >
                <span style={{ fontSize: TYPE.sm, color: t.textMuted }}>
                  Page {meta.current_page} of {meta.last_page} ({meta.total} total)
                </span>
                <div style={{ display: "flex", gap: 8 }}>
                  <Button variant="ghost" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                    Previous
                  </Button>
                  <Button variant="ghost" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
                    Next
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </Card>

      <Modal open={createOpen} onClose={() => !createSubmitting && setCreateOpen(false)} title="Create super admin" width={460}>
        <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <Input label="Name" value={createForm.name} onChange={(v) => setCreateForm((f) => ({ ...f, name: v }))} placeholder="Full name" />
          <Input
            label="Email"
            value={createForm.email}
            onChange={(v) => setCreateForm((f) => ({ ...f, email: v }))}
            placeholder="admin@company.com"
          />
          <Input
            label="Phone (optional)"
            value={createForm.phone_number}
            onChange={(v) => setCreateForm((f) => ({ ...f, phone_number: v }))}
            placeholder="+1 555 000 1111"
          />
          {!createPhoneOk && (
            <div style={{ fontSize: TYPE.sm, color: t.danger }}>Enter a valid phone number or leave blank.</div>
          )}
          <div style={{ fontSize: TYPE.sm, color: t.textMuted }}>
            A secure 4-digit PIN is generated automatically and emailed to this address. It is never shown in the admin UI.
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", marginTop: 8 }}>
            <Button variant="ghost" onClick={() => !createSubmitting && setCreateOpen(false)} disabled={createSubmitting}>
              Cancel
            </Button>
            <Button variant="primary" onClick={submitCreate} disabled={createSubmitting}>
              {createSubmitting ? "Creating…" : "Create & send email"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal open={editOpen} onClose={() => !editSubmitting && setEditOpen(false)} title="Edit super admin" width={460}>
        <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <Input label="Name" value={editForm.name} onChange={(v) => setEditForm((f) => ({ ...f, name: v }))} />
          <Input label="Email" value={editForm.email} onChange={(v) => setEditForm((f) => ({ ...f, email: v }))} />
          <Input label="Phone (optional)" value={editForm.phone_number} onChange={(v) => setEditForm((f) => ({ ...f, phone_number: v }))} />
          {!editPhoneOk && (
            <div style={{ fontSize: TYPE.sm, color: t.danger }}>Enter a valid phone number or leave blank.</div>
          )}
          <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", color: t.text }}>
            <input
              type="checkbox"
              checked={!!editForm.is_active}
              onChange={(e) => setEditForm((f) => ({ ...f, is_active: e.target.checked }))}
              style={{ accentColor: t.accent }}
            />
            <span style={{ fontSize: TYPE.sm }}>Active (can sign in)</span>
          </label>
          <div style={{ fontSize: TYPE.sm, color: t.textMuted }}>PIN is never shown here. Use Reset PIN to issue a new one.</div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", marginTop: 8 }}>
            <Button variant="ghost" onClick={() => !editSubmitting && setEditOpen(false)} disabled={editSubmitting}>
              Cancel
            </Button>
            <Button variant="primary" onClick={submitEdit} disabled={editSubmitting}>
              {editSubmitting ? "Saving…" : "Save"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal
        open={!!statusConfirm?.row}
        onClose={() => !statusBusy && setStatusConfirm(null)}
        title={statusConfirm?.nextActive ? "Activate admin?" : "Deactivate admin?"}
        width={420}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.5 }}>
            {statusConfirm?.nextActive
              ? `${statusConfirm?.row?.email ?? ""} will be allowed to sign in again.`
              : `${statusConfirm?.row?.email ?? ""} will be blocked from signing in. Existing sessions are revoked when deactivated.`}
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button variant="ghost" onClick={() => !statusBusy && setStatusConfirm(null)} disabled={statusBusy}>
              Cancel
            </Button>
            <Button variant={statusConfirm?.nextActive ? "primary" : "danger"} onClick={applyStatusChange} disabled={statusBusy}>
              {statusBusy ? "Updating…" : "Confirm"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal open={!!resetConfirm?.row} onClose={() => !resetBusy && setResetConfirm(null)} title="Reset PIN?" width={420}>
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.5 }}>
            A new 4-digit PIN will be generated for {resetConfirm?.row?.email ?? ""}. The old PIN stops working immediately and refresh tokens are
            cleared. The new PIN will be emailed.
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button variant="ghost" onClick={() => !resetBusy && setResetConfirm(null)} disabled={resetBusy}>
              Cancel
            </Button>
            <Button variant="primary" onClick={applyResetPin} disabled={resetBusy}>
              {resetBusy ? "Resetting…" : "Reset PIN"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal open={!!deleteConfirm?.row} onClose={() => !deleteBusy && setDeleteConfirm(null)} title="Delete super admin?" width={420}>
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm, lineHeight: 1.5 }}>
            Permanently remove {deleteConfirm?.row?.email ?? ""}? Their PIN, OTPs, and refresh tokens will be deleted. This cannot be undone.
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button variant="ghost" onClick={() => !deleteBusy && setDeleteConfirm(null)} disabled={deleteBusy}>
              Cancel
            </Button>
            <Button variant="danger" onClick={applyDelete} disabled={deleteBusy}>
              {deleteBusy ? "Deleting…" : "Delete"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
