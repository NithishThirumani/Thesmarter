import React, { useState } from "react";
import { useOutletContext } from "react-router-dom";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import { MOCK_TENANTS } from "../../theSmartr";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Badge from "../../components/Badge";
import Card from "../../components/Card";
import DataTable from "../../components/DataTable";
import Modal from "../../components/Modal";
import Input from "../../components/Input";
import Select from "../../components/Select";

export default function TenantPage() {
    const { addToast } = useOutletContext() || {};
    const addToastSafe = addToast || (() => {});
    const { tokens: t } = useTheme();
    const [tenants, setTenants] = useState(MOCK_TENANTS);
    const [modalOpen, setModalOpen] = useState(false);
    const [editTenant, setEditTenant] = useState(null);
    const [form, setForm] = useState({ name: "", code: "", domain: "", email: "", plan: "Starter", status: "Trial" });

    const cols = [
        { key: "name", label: "Tenant", render: (v, row) => <span style={{ fontWeight: TYPE.semibold, color: t.text, lineHeight: 1.5 }}>{v}<br /><span style={{ fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontMono }}>{row.code}</span></span> },
        { key: "domain", label: "Domain", render: v => <span style={{ fontSize: TYPE.sm, color: t.textSecondary, fontFamily: TYPE.fontMono }}>{v}</span> },
        { key: "plan", label: "Plan", render: v => <Badge status={v} /> },
        { key: "status", label: "Status", render: v => <Badge status={v} /> },
        { key: "users", label: "Users", render: v => <span style={{ color: t.textSecondary, fontVariantNumeric: "tabular-nums" }}>{v}</span> },
        { key: "expiry", label: "Expiry", render: v => <span style={{ fontSize: TYPE.sm, color: t.textMuted }}>{v}</span> },
    ];

    const openCreate = () => { setEditTenant(null); setForm({ name: "", code: "", domain: "", email: "", plan: "Starter", status: "Trial" }); setModalOpen(true); };
    const openEdit = row => { setEditTenant(row); setForm({ name: row.name, code: row.code, domain: row.domain, email: row.email, plan: row.plan, status: row.status }); setModalOpen(true); };
    const handleSave = () => {
        if (editTenant) {
            setTenants(ts => ts.map(t => t.id === editTenant.id ? { ...t, ...form } : t));
            addToastSafe("success", "Tenant Updated", `${form.name} has been updated.`);
        } else {
            setTenants(ts => [...ts, { ...form, id: Date.now(), created: new Date().toISOString().slice(0, 10), expiry: "2025-12-31", users: 0, revenue: 0 }]);
            addToastSafe("success", "Tenant Created", `${form.name} is now active.`);
        }
        setModalOpen(false);
    };
    const handleDelete = row => { setTenants(ts => ts.filter(t => t.id !== row.id)); addToastSafe("error", "Tenant Removed", `${row.name} has been deleted.`); };

    return (
        <div>
            <PageHeader title="Tenant Management" subtitle="Manage multi-tenant ERP clients" breadcrumb="Tenants"
                action={<Button variant="primary" icon="+" onClick={openCreate}>New Tenant</Button>} />
            <Card>
                <DataTable columns={cols} data={tenants} onEdit={openEdit} onDelete={handleDelete} emptyIcon="⬡" emptyTitle="No Tenants" emptyMsg="Create your first tenant to get started." />
            </Card>
            <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editTenant ? "Edit Tenant" : "Create Tenant"}>
                <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
                    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
                        <Input label="Tenant Name" value={form.name} onChange={v => setForm(f => ({ ...f, name: v }))} placeholder="Acme Corp" />
                        <Input label="Tenant Code" value={form.code} onChange={v => setForm(f => ({ ...f, code: v }))} placeholder="ACME001" />
                    </div>
                    <Input label="Domain / Subdomain" value={form.domain} onChange={v => setForm(f => ({ ...f, domain: v }))} placeholder="acme.thesmartr.com" />
                    <Input label="Contact Email" value={form.email} onChange={v => setForm(f => ({ ...f, email: v }))} placeholder="admin@acme.com" />
                    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
                        <Select label="Plan" value={form.plan} onChange={v => setForm(f => ({ ...f, plan: v }))} options={[{ value: "Starter", label: "Starter" }, { value: "Pro", label: "Pro" }, { value: "Enterprise", label: "Enterprise" }]} />
                        <Select label="Status" value={form.status} onChange={v => setForm(f => ({ ...f, status: v }))} options={[{ value: "Trial", label: "Trial" }, { value: "Active", label: "Active" }, { value: "Suspended", label: "Suspended" }]} />
                    </div>
                    <div style={{ display: "flex", gap: 10, justifyContent: "flex-end", marginTop: 8 }}>
                        <Button variant="ghost" onClick={() => setModalOpen(false)}>Cancel</Button>
                        <Button variant="primary" onClick={handleSave}>{editTenant ? "Save Changes" : "Create Tenant"}</Button>
                    </div>
                </div>
            </Modal>
        </div>
    );
}