import React, { useCallback, useEffect, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as featureService from "../../features/featureService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Modal from "../../components/Modal";

const EMPTY_FORM = {
  feature_name: "",
  feature_type: "",
  feature_description: "",
  feature_status: "A",
};

export default function FeaturePage() {
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const [list, setList] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [searchDebounce, setSearchDebounce] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  const [formOpen, setFormOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [editTarget, setEditTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);

  const load = useCallback(async () => {
    if (!accessToken) return;
    setLoading(true);
    try {
      const res = await featureService.listFeatures(
        {
          page,
          per_page: 15,
          search: searchDebounce || undefined,
          feature_status: statusFilter,
        },
        accessToken
      );
      setList(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (e) {
      setList([]);
      addToastSafe("error", "Error", e.message);
    } finally {
      setLoading(false);
    }
  }, [accessToken, addToastSafe, page, searchDebounce, statusFilter]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    const id = setTimeout(() => setSearchDebounce(search), 300);
    return () => clearTimeout(id);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [searchDebounce, statusFilter]);

  const toStatusLabel = (status) => {
    const active =
      status === "A" || status === 1 || status === "1" || status === "Active" || status === true;
    return active ? "Active" : "Inactive";
  };

  const openCreate = () => {
    setEditTarget(null);
    setForm(EMPTY_FORM);
    setFormOpen(true);
  };

  const openEdit = (row) => {
    setEditTarget(row);
    setForm({
      feature_name: row.feature_name || "",
      feature_type: row.feature_type || "",
      feature_description: row.feature_description || "",
      feature_status: toStatusLabel(row.feature_status) === "Active" ? "A" : "D",
    });
    setFormOpen(true);
  };

  const saveForm = async () => {
    if (!form.feature_name.trim()) {
      addToastSafe("error", "Validation", "Feature name is required.");
      return;
    }
    if (!accessToken) return;
    setSaving(true);
    try {
      const payload = {
        feature_name: form.feature_name.trim(),
        feature_type: form.feature_type.trim() || null,
        feature_description: form.feature_description.trim() || null,
        feature_status: form.feature_status,
      };
      if (editTarget) {
        await featureService.updateFeature(editTarget.feature_id, payload, accessToken);
        addToastSafe("success", "Updated", "Feature updated successfully.");
      } else {
        await featureService.createFeature(payload, accessToken);
        addToastSafe("success", "Created", "Feature created successfully.");
      }
      setFormOpen(false);
      await load();
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = async () => {
    if (!deleteTarget || !accessToken) return;
    setDeleting(true);
    try {
      await featureService.deleteFeature(deleteTarget.feature_id, accessToken);
      addToastSafe("success", "Deleted", `${deleteTarget.feature_name} deleted.`);
      setDeleteOpen(false);
      setDeleteTarget(null);
      await load();
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <PageHeader
        title="Features"
        subtitle="Manage platform features"
        breadcrumb="Features"
        action={
          <Button variant="primary" icon="+" onClick={openCreate}>
            New Feature
          </Button>
        }
      />

      <Card>
        <div style={{ display: "flex", gap: 12, marginBottom: 16, flexWrap: "wrap", alignItems: "end" }}>
          <div style={{ flex: 1, minWidth: 260 }}>
            <Input value={search} onChange={setSearch} placeholder="Search by name, type or description..." />
          </div>
          <div style={{ minWidth: 180 }}>
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
          </div>
        </div>

        {loading ? (
          <div style={{ padding: 24, color: t.textMuted }}>Loading features...</div>
        ) : list.length === 0 ? (
          <div style={{ padding: 48, textAlign: "center", color: t.textMuted }}>
            No features found.
          </div>
        ) : (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase" }}>Name</th>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase" }}>Type</th>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase" }}>Description</th>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase", width: 140 }}>Status</th>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase", textAlign: "right", width: 220 }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {list.map((row) => (
                  <tr key={row.feature_id} style={{ borderBottom: `1px solid ${t.border}` }}>
                    <td style={{ padding: "14px 16px", color: t.text, fontWeight: TYPE.semibold }}>{row.feature_name}</td>
                    <td style={{ padding: "14px 16px", color: t.textSecondary }}>{row.feature_type || "-"}</td>
                    <td style={{ padding: "14px 16px", color: t.textSecondary, maxWidth: 500, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                      {row.feature_description || "-"}
                    </td>
                    <td style={{ padding: "14px 16px" }}>
                      <Badge status={toStatusLabel(row.feature_status)}>{toStatusLabel(row.feature_status)}</Badge>
                    </td>
                    <td style={{ padding: "14px 16px", textAlign: "right" }}>
                      <div style={{ display: "flex", gap: 8, justifyContent: "flex-end" }}>
                        <Button variant="ghost" size="sm" icon="✎" onClick={() => openEdit(row)} />
                        <Button
                          variant="danger"
                          size="sm"
                          icon="🗑"
                          onClick={() => {
                            setDeleteTarget(row);
                            setDeleteOpen(true);
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
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginTop: 16, paddingTop: 12, borderTop: `1px solid ${t.border}` }}>
            <span style={{ color: t.textMuted, fontSize: TYPE.sm }}>
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
      </Card>

      <Modal
        open={formOpen}
        onClose={() => !saving && setFormOpen(false)}
        title={editTarget ? "Edit Feature" : "Create Feature"}
        width={620}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <Input label="Feature Name" value={form.feature_name} onChange={(v) => setForm((p) => ({ ...p, feature_name: v }))} placeholder="e.g. Inventory Sync" />
          <Input
            label="Feature Type"
            value={form.feature_type}
            onChange={(v) => setForm((p) => ({ ...p, feature_type: v.slice(0, 255) }))}
            placeholder="e.g. CORE"
          />
          <Input label="Description" value={form.feature_description} onChange={(v) => setForm((p) => ({ ...p, feature_description: v }))} placeholder="Describe what this feature does" />
          <Select
            label="Status"
            value={form.feature_status}
            onChange={(v) => setForm((p) => ({ ...p, feature_status: v }))}
            options={[
              { value: "A", label: "Active" },
              { value: "D", label: "Inactive" },
            ]}
          />

          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 8 }}>
            <Button variant="ghost" onClick={() => !saving && setFormOpen(false)} disabled={saving}>
              Cancel
            </Button>
            <Button variant="primary" onClick={saveForm} disabled={saving}>
              {saving ? "Saving..." : editTarget ? "Update Feature" : "Create Feature"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal open={deleteOpen} onClose={() => !deleting && setDeleteOpen(false)} title="Delete feature?" width={480}>
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            This will permanently delete <strong style={{ color: t.text }}>{deleteTarget?.feature_name}</strong>.
          </div>
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10 }}>
            <Button variant="ghost" onClick={() => !deleting && setDeleteOpen(false)} disabled={deleting}>
              Cancel
            </Button>
            <Button variant="danger" onClick={confirmDelete} disabled={deleting}>
              {deleting ? "Deleting..." : "Delete"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
