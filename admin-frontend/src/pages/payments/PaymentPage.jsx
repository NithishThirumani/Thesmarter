import React, { useCallback, useEffect, useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as paymentMethodService from "../../payments/paymentMethodService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Modal from "../../components/Modal";

/** Must match backend App\Support\PaymentMethodAllowedTypes. */
const ALLOWED_PAYMENT_TYPES = [
  "Card",
  "Mobile",
  "Digital Wallet",
  "Cash",
  "BNPL",
  "Cryptocurrencies",
  "Wire",
  "Others",
];

const PAYMENT_TYPE_SELECT_OPTIONS = ALLOWED_PAYMENT_TYPES.map((v) => ({ value: v, label: v }));

function normalizePaymentTypeForForm(raw) {
  const s = typeof raw === "string" ? raw.trim() : String(raw ?? "");
  if (ALLOWED_PAYMENT_TYPES.includes(s)) return s;
  if (s !== "" && !Number.isNaN(Number(s)) && /^-?\d+$/.test(String(s).trim())) {
    const k = Number.parseInt(String(s).trim(), 10);
    if (k === 1) return "Cash";
    if (k === 2) return "Card";
    if (k === 3) return "Digital Wallet";
  }
  return "Others";
}

const EMPTY_FORM = {
  payment_name: "",
  payment_type: "Others",
  payment_description: "",
  active_status: 1,
};

export default function PaymentPage() {
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
      const res = await paymentMethodService.listPaymentMethods(
        {
          page,
          per_page: 15,
          search: searchDebounce || undefined,
          payment_status: statusFilter,
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

  const isActiveRow = (row) => row.active_status === 1 || row.active_status === "1";

  const openCreate = () => {
    setEditTarget(null);
    setForm(EMPTY_FORM);
    setFormOpen(true);
  };

  const openEdit = (row) => {
    setEditTarget(row);
    setForm({
      payment_name: row.payment_name || "",
      payment_type: normalizePaymentTypeForForm(row.payment_type),
      payment_description: row.payment_description || "",
      active_status: isActiveRow(row) ? 1 : 0,
    });
    setFormOpen(true);
  };

  const saveForm = async () => {
    if (!form.payment_name.trim()) {
      addToastSafe("error", "Validation", "Payment name is required.");
      return;
    }
    if (!form.payment_type || !ALLOWED_PAYMENT_TYPES.includes(form.payment_type)) {
      addToastSafe("error", "Validation", "Select a payment type.");
      return;
    }
    if (!accessToken) return;
    setSaving(true);
    try {
      const payload = {
        payment_name: form.payment_name.trim(),
        payment_type: normalizePaymentTypeForForm(form.payment_type),
        payment_description: form.payment_description.trim() || null,
        active_status: Number(form.active_status),
      };
      if (editTarget) {
        await paymentMethodService.updatePaymentMethod(editTarget.payment_id, payload, accessToken);
        addToastSafe("success", "Updated", "Payment method updated.");
      } else {
        await paymentMethodService.createPaymentMethod(payload, accessToken);
        addToastSafe("success", "Created", "Payment method created.");
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
      await paymentMethodService.deletePaymentMethod(deleteTarget.payment_id, accessToken);
      addToastSafe("success", "Deleted", `${deleteTarget.payment_name} deleted.`);
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
        title="Payments"
        subtitle="Platform payment methods — names, types, and availability for companies"
        breadcrumb="Payments"
        action={
          <Button variant="primary" icon="+" onClick={openCreate}>
            New payment method
          </Button>
        }
      />

      <Card>
        <div style={{ display: "flex", gap: 12, marginBottom: 16, flexWrap: "wrap", alignItems: "end" }}>
          <div style={{ flex: 1, minWidth: 260 }}>
            <Input value={search} onChange={setSearch} placeholder="Search by name, description, or type…" />
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
          <div style={{ padding: 24, color: t.textMuted }}>Loading payment methods...</div>
        ) : list.length === 0 ? (
          <div style={{ padding: 48, textAlign: "center", color: t.textMuted }}>
            No payment methods found.
          </div>
        ) : (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: TYPE.fontBody }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${t.border}`, textAlign: "left" }}>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase" }}>
                    Name
                  </th>
                  <th style={{ padding: "12px 16px", color: t.textMuted, fontSize: TYPE.xs, textTransform: "uppercase" }}>
                    Type
                  </th>
                  <th
                    style={{
                      padding: "12px 16px",
                      color: t.textMuted,
                      fontSize: TYPE.xs,
                      textTransform: "uppercase",
                    }}
                  >
                    Description
                  </th>
                  <th
                    style={{
                      padding: "12px 16px",
                      color: t.textMuted,
                      fontSize: TYPE.xs,
                      textTransform: "uppercase",
                      width: 140,
                    }}
                  >
                    Status
                  </th>
                  <th
                    style={{
                      padding: "12px 16px",
                      color: t.textMuted,
                      fontSize: TYPE.xs,
                      textTransform: "uppercase",
                      textAlign: "right",
                      width: 220,
                    }}
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {list.map((row) => (
                  <tr key={row.payment_id} style={{ borderBottom: `1px solid ${t.border}` }}>
                    <td style={{ padding: "14px 16px", color: t.text, fontWeight: TYPE.semibold }}>{row.payment_name}</td>
                    <td style={{ padding: "14px 16px", color: t.textSecondary }}>{normalizePaymentTypeForForm(row.payment_type)}</td>
                    <td
                      style={{
                        padding: "14px 16px",
                        color: t.textSecondary,
                        maxWidth: 420,
                        whiteSpace: "nowrap",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                      }}
                    >
                      {row.payment_description || "—"}
                    </td>
                    <td style={{ padding: "14px 16px" }}>
                      <Badge status={isActiveRow(row) ? "Active" : "Inactive"}>
                        {isActiveRow(row) ? "Active" : "Inactive"}
                      </Badge>
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
          <div
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "center",
              marginTop: 16,
              paddingTop: 12,
              borderTop: `1px solid ${t.border}`,
            }}
          >
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
        title={editTarget ? "Edit payment method" : "New payment method"}
        width={620}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <Input
            label="Name"
            value={form.payment_name}
            onChange={(v) => setForm((p) => ({ ...p, payment_name: v }))}
            placeholder="e.g. Cash on delivery"
          />
          <Select
            label="Payment type"
            value={form.payment_type}
            onChange={(v) => setForm((p) => ({ ...p, payment_type: v }))}
            options={PAYMENT_TYPE_SELECT_OPTIONS}
          />
          <Input
            label="Description"
            value={form.payment_description}
            onChange={(v) => setForm((p) => ({ ...p, payment_description: v }))}
            placeholder="Optional description for admins"
          />
          <Select
            label="Status"
            value={String(form.active_status)}
            onChange={(v) => setForm((p) => ({ ...p, active_status: Number(v) }))}
            options={[
              { value: "1", label: "Active" },
              { value: "0", label: "Inactive" },
            ]}
          />

          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 8 }}>
            <Button variant="ghost" onClick={() => !saving && setFormOpen(false)} disabled={saving}>
              Cancel
            </Button>
            <Button variant="primary" onClick={saveForm} disabled={saving}>
              {saving ? "Saving..." : editTarget ? "Update" : "Create"}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal open={deleteOpen} onClose={() => !deleting && setDeleteOpen(false)} title="Delete payment method?" width={480}>
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            This will permanently delete <strong style={{ color: t.text }}>{deleteTarget?.payment_name}</strong>. This is only
            allowed when no company or order references this method.
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
