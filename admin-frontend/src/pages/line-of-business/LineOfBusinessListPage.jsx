import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Modal from "../../components/Modal";
import Badge from "../../components/Badge";
import LineOfBusinessTableRowSkeleton from "../../components/skeleton/LineOfBusinessTableRowSkeleton";

export default function LineOfBusinessListPage() {
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [page, setPage] = useState(1);

  const [search, setSearch] = useState("");
  const [searchDebounce, setSearchDebounce] = useState("");
  const [lobStatus, setLobStatus] = useState("all"); // all|active|inactive

  const [filterOpen, setFilterOpen] = useState(false);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await lobService.listLineOfBusiness(
        {
          page,
          per_page: 15,
          search: searchDebounce || undefined,
          lob_status:
            lobStatus === "all" ? "all" : lobStatus === "active" ? "A" : "I",
        },
        accessToken
      );
      setList(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
      setList([]);
    } finally {
      setLoading(false);
    }
  }, [accessToken, page, searchDebounce, lobStatus]);

  // initial load / reload on filters
  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounce(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [searchDebounce, lobStatus]);

  const statusLabel = (status) => {
    if (status === "A" || status === 1 || status === "Active") return "Active";
    return "Inactive";
  };

  const confirmDelete = async () => {
    if (!deleteTarget || !accessToken) return;
    setDeleting(true);
    try {
      await lobService.deleteLineOfBusiness(deleteTarget.lob_id, accessToken);
      addToastSafe("success", "Deleted", `${deleteTarget.lob_name} deleted.`);
      setDeleteModalOpen(false);
      setDeleteTarget(null);
      await load();
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader
        title="Line of Business"
        subtitle="Manage business categories"
        breadcrumb="Line of Business"
        action={
          <div style={{ display: "flex", gap: 10, alignItems: "center" }}>
            <Button variant="ghost" size="sm" icon="⚙" onClick={() => setFilterOpen((v) => !v)}>
              Filters
            </Button>
            <Button variant="primary" icon="+" onClick={() => navigate("/line-of-business/new")}>
              New
            </Button>
          </div>
        }
      />

      <Card>
        <div style={{ display: "flex", gap: 12, alignItems: "center", marginBottom: 16, flexWrap: "wrap" }}>
          <div style={{ flex: 1, minWidth: 260 }}>
            <Input value={search} onChange={setSearch} placeholder="Search by name or description…" />
          </div>

          {filterOpen && (
            <div style={{ width: 320, padding: 14, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgCard }}>
              <div style={{ color: t.textSecondary, fontWeight: TYPE.semibold, marginBottom: 10, fontSize: TYPE.sm }}>
                Filter by status
              </div>
              <div style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
                <Button
                  variant={lobStatus === "all" ? "dark" : "ghost"}
                  size="sm"
                  onClick={() => setLobStatus("all")}
                >
                  All
                </Button>
                <Button
                  variant={lobStatus === "active" ? "dark" : "ghost"}
                  size="sm"
                  onClick={() => setLobStatus("active")}
                >
                  Active
                </Button>
                <Button
                  variant={lobStatus === "inactive" ? "dark" : "ghost"}
                  size="sm"
                  onClick={() => setLobStatus("inactive")}
                >
                  Inactive
                </Button>
              </div>
              <div style={{ display: "flex", justifyContent: "flex-end", marginTop: 12, gap: 10 }}>
                <Button variant="ghost" size="sm" onClick={() => setFilterOpen(false)}>
                  Close
                </Button>
              </div>
            </div>
          )}
        </div>

        {loading ? (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                    Name
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                    Description
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", width: 160 }}>
                    Status
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", textAlign: "right", width: 240 }}>
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {Array.from({ length: 6 }).map((_, i) => (
                  <LineOfBusinessTableRowSkeleton key={i} />
                ))}
              </tbody>
            </table>
          </div>
        ) : list.length === 0 ? (
          <div style={{ padding: 48, textAlign: "center", color: t.textMuted }}>
            No records.
          </div>
        ) : (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                    Name
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase" }}>
                    Description
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", width: 160 }}>
                    Status
                  </th>
                  <th style={{ padding: "12px 16px", fontSize: TYPE.xs, fontWeight: TYPE.bold, color: t.textMuted, textTransform: "uppercase", textAlign: "right", width: 240 }}>
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {list.map((row) => (
                  <tr key={row.lob_id} style={{ borderBottom: `1px solid ${t.border}` }}>
                    <td style={{ padding: "14px 16px", color: t.text, fontWeight: TYPE.semibold }}>{row.lob_name}</td>
                    <td style={{ padding: "14px 16px", color: t.textSecondary, maxWidth: 520, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                      {row.lob_description || "—"}
                    </td>
                    <td style={{ padding: "14px 16px" }}>
                      <Badge status={statusLabel(row.lob_status)}>{statusLabel(row.lob_status)}</Badge>
                    </td>
                    <td style={{ padding: "14px 16px", textAlign: "right" }}>
                      <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
                        <Button
                          variant="ghost"
                          size="sm"
                          icon="👁"
                          onClick={() => navigate(`/line-of-business/${row.lob_id}`)}
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon="✎"
                          onClick={() => navigate(`/line-of-business/${row.lob_id}/edit`)}
                        />
                        <Button
                          variant="danger"
                          size="sm"
                          icon="🗑"
                          onClick={() => {
                            setDeleteTarget(row);
                            setDeleteModalOpen(true);
                          }}
                        />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", paddingTop: 16, marginTop: 16, borderTop: `1px solid ${t.border}` }}>
            <span style={{ color: t.textMuted, fontSize: TYPE.sm }}>
              Page {meta.current_page} of {meta.last_page} ({meta.total} total)
            </span>
            <div style={{ display: "flex", gap: 8 }}>
              <Button
                variant="ghost"
                size="sm"
                disabled={meta.current_page <= 1}
                onClick={() => setPage(Math.max(1, meta.current_page - 1))}
              >
                Previous
              </Button>
              <Button
                variant="ghost"
                size="sm"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage(meta.current_page + 1)}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </Card>

      <Modal open={deleteModalOpen} onClose={() => !deleting && setDeleteModalOpen(false)} title="Delete line of business?" width={480}>
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            This will permanently delete <strong style={{ color: t.text }}>{deleteTarget?.lob_name}</strong>.
          </div>
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button variant="ghost" onClick={() => !deleting && setDeleteModalOpen(false)} disabled={deleting}>
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

