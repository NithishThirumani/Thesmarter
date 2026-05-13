import React, { useEffect, useState } from "react";
import { useNavigate, useOutletContext, useParams } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Modal from "../../components/Modal";
import TextSkeleton from "../../components/skeleton/TextSkeleton";

export default function LineOfBusinessEditPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ lob_name: "", lob_description: "", lob_status: "A" });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [confirmOpen, setConfirmOpen] = useState(false);

  useEffect(() => {
    if (!id || !accessToken) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const res = await lobService.getLineOfBusiness(id, accessToken);
        if (cancelled) return;
        setForm({
          lob_name: res.data?.lob_name || "",
          lob_description: res.data?.lob_description || "",
          lob_status: res.data?.lob_status || "A",
        });
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Error", e.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [id, accessToken, addToastSafe]);

  const validate = () => {
    if (!form.lob_name.trim()) return "Line of business name is required.";
    return "";
  };

  const handleUpdate = async () => {
    const v = validate();
    if (v) {
      setError(v);
      addToastSafe("error", "Validation", v);
      return;
    }
    setError("");
    setSubmitting(true);
    try {
      const payload = {
        lob_name: form.lob_name.trim(),
        lob_description: form.lob_description.trim() || null,
        lob_status: form.lob_status,
      };
      await lobService.updateLineOfBusiness(id, payload, accessToken);
      addToastSafe("success", "Updated", "Line of business updated.");
      setConfirmOpen(false);
      navigate(`/line-of-business/${id}`);
    } catch (e) {
      setError(e.message);
      addToastSafe("error", "Update failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div>
        <PageHeader title="Edit Line of Business" breadcrumb="Line of Business" />
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            <TextSkeleton width={240} height={14} />
            <TextSkeleton width={340} height={12} />
            <TextSkeleton width={280} height={12} />
            <TextSkeleton width={"100%"} height={12} />
            <TextSkeleton width={"75%"} height={12} />
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader title="Edit Line of Business" subtitle="Update details" breadcrumb="Line of Business → Edit" />
      <Card>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
          <Input label="Name" value={form.lob_name} onChange={(v) => setForm((f) => ({ ...f, lob_name: v }))} placeholder="Name" />
          <Select
            label="Status"
            value={form.lob_status}
            onChange={(v) => setForm((f) => ({ ...f, lob_status: v }))}
            options={[
              { value: "A", label: "Active" },
              { value: "I", label: "Inactive" },
            ]}
          />
        </div>

        <div style={{ marginTop: 14 }}>
          <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase", fontFamily: TYPE.fontBody }}>
            Description
          </label>
          <textarea
            value={form.lob_description}
            onChange={(e) => setForm((f) => ({ ...f, lob_description: e.target.value }))}
            placeholder="Optional description…"
            style={{
              width: "100%",
              minHeight: 120,
              marginTop: 8,
              padding: "10px 12px",
              background: t.bgElevated,
              border: `1px solid ${t.border}`,
              borderRadius: 6,
              color: t.text,
              fontSize: TYPE.base,
              fontFamily: TYPE.fontBody,
              outline: "none",
              resize: "vertical",
            }}
          />
        </div>

        {error && <div style={{ marginTop: 14, color: t.danger, fontSize: TYPE.sm }}>{error}</div>}

        <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 18 }}>
          <Button variant="ghost" onClick={() => navigate(`/line-of-business/${id}`)}>
            Cancel
          </Button>
          <Button variant="primary" onClick={() => setConfirmOpen(true)} disabled={submitting}>
            {submitting ? "Updating…" : "Update"}
          </Button>
        </div>
      </Card>

      <Modal open={confirmOpen} onClose={() => !submitting && setConfirmOpen(false)} title="Confirm update" width={480}>
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            You are about to update <strong style={{ color: t.text }}>{form.lob_name || "this item"}</strong>.
          </div>
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10 }}>
            <Button variant="ghost" onClick={() => !submitting && setConfirmOpen(false)} disabled={submitting}>
              Cancel
            </Button>
            <Button variant="primary" onClick={handleUpdate} disabled={submitting}>
              {submitting ? "Updating…" : "Confirm"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}

