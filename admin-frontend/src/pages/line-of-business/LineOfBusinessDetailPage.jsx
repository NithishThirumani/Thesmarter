import React, { useEffect, useState } from "react";
import { useNavigate, useOutletContext, useParams } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Badge from "../../components/Badge";
import Modal from "../../components/Modal";
import TextSkeleton from "../../components/skeleton/TextSkeleton";

export default function LineOfBusinessDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [item, setItem] = useState(null);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const statusLabel = (status) => {
    if (status === "A" || status === 1 || status === "Active") return "Active";
    return "Inactive";
  };

  useEffect(() => {
    if (!id || !accessToken) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const res = await lobService.getLineOfBusiness(id, accessToken);
        if (cancelled) return;
        setItem(res.data || null);
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

  const confirmDelete = async () => {
    if (!item || !accessToken) return;
    setDeleting(true);
    try {
      await lobService.deleteLineOfBusiness(id, accessToken);
      addToastSafe("success", "Deleted", "Line of business deleted.");
      setDeleteModalOpen(false);
      navigate("/line-of-business");
    } catch (e) {
      addToastSafe("error", "Delete failed", e.message);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader
        title={item?.lob_name || "Line of Business"}
        subtitle={item?.lob_description ? item.lob_description : undefined}
        breadcrumb="Line of Business"
        action={
          <div style={{ display: "flex", gap: 8 }}>
            <Button variant="ghost" onClick={() => navigate("/line-of-business")}>
              Back
            </Button>
            <Button variant="ghost" icon="✎" onClick={() => navigate(`/line-of-business/${id}/edit`)}>
              Edit
            </Button>
            <Button variant="danger" icon="🗑" onClick={() => setDeleteModalOpen(true)}>
              Delete
            </Button>
          </div>
        }
      />

      {loading || !item ? (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            <TextSkeleton width={260} height={18} style={{ borderRadius: 10 }} />
            <TextSkeleton width={420} height={12} />
            <TextSkeleton width={380} height={12} />
            <TextSkeleton width={"100%"} height={12} />
            <TextSkeleton width={"60%"} height={12} />
          </div>
        </Card>
      ) : (
        <div style={{ display: "grid", gap: 18 }}>
          <Card title="Details">
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              <div>
                <div style={{ fontSize: TYPE.xs, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: TYPE.bold }}>
                  Name
                </div>
                <div style={{ marginTop: 8, color: t.text, fontWeight: TYPE.semibold }}>{item.lob_name}</div>
              </div>
              <div>
                <div style={{ fontSize: TYPE.xs, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: TYPE.bold }}>
                  Status
                </div>
                <div style={{ marginTop: 8 }}>
                  <Badge status={statusLabel(item.lob_status)}>{statusLabel(item.lob_status)}</Badge>
                </div>
              </div>
            </div>
            <div style={{ marginTop: 16 }}>
              <div style={{ fontSize: TYPE.xs, color: t.textMuted, textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: TYPE.bold }}>
                Description
              </div>
              <div style={{ marginTop: 8, color: t.textSecondary, lineHeight: 1.6 }}>{item.lob_description || "—"}</div>
            </div>
          </Card>
        </div>
      )}

      <Modal open={deleteModalOpen} onClose={() => !deleting && setDeleteModalOpen(false)} title="Delete line of business?" width={480}>
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            This will permanently delete <strong style={{ color: t.text }}>{item?.lob_name}</strong>.
          </div>
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10 }}>
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

