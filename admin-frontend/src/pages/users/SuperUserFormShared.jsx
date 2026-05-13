import React, { useMemo } from "react";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Button from "../../components/Button";

export const GENDER_OPTIONS = [
  { value: "", label: "Select…" },
  { value: "M", label: "Male" },
  { value: "F", label: "Female" },
  { value: "O", label: "Other" },
];

export const MARITAL_OPTIONS = [
  { value: "", label: "Select…" },
  { value: "S", label: "Single" },
  { value: "M", label: "Married" },
  { value: "D", label: "Divorced" },
  { value: "W", label: "Widowed" },
  { value: "NA", label: "Prefer not to say" },
];

export function formatMarital(code) {
  if (!code) return "—";
  const m = MARITAL_OPTIONS.find((o) => o.value === code);
  return m ? m.label : code || "—";
}

export function formatGender(code) {
  if (!code) return "—";
  const g = GENDER_OPTIONS.find((o) => o.value === code);
  return g ? g.label : code || "—";
}

function initialsFromName(first, last) {
  const a = String(first || "").trim().charAt(0);
  const b = String(last || "").trim().charAt(0);
  return (a + b).toUpperCase() || "—";
}

/** Photo upload with live preview, or initials avatar when no image. */
export function SuperUserAvatarPicker({ existingImageUrl, file, onFileChange, onClearFile, firstName, lastName, disabled, label = "Profile photo" }) {
  const { tokens: t } = useTheme();
  const preview = useMemo(() => {
    if (file) return URL.createObjectURL(file);
    return null;
  }, [file]);

  React.useEffect(() => {
    return () => {
      if (preview && preview.startsWith("blob:")) URL.revokeObjectURL(preview);
    };
  }, [preview]);

  const showUrl = preview || existingImageUrl;
  const initials = initialsFromName(firstName, lastName);

  return (
    <div style={{ marginBottom: 4 }}>
      <span
        style={{
          fontSize: TYPE.xs,
          fontWeight: TYPE.semibold,
          color: t.textSecondary,
          letterSpacing: "0.06em",
          textTransform: "uppercase",
          fontFamily: TYPE.fontBody,
          display: "block",
          marginBottom: 10,
        }}
      >
        {label}
      </span>
      <div style={{ display: "flex", flexWrap: "wrap", alignItems: "center", gap: 16 }}>
        <div
          style={{
            width: 96,
            height: 96,
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
          {showUrl ? <img src={showUrl} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} /> : <span>{initials}</span>}
        </div>
        <div style={{ display: "flex", flexDirection: "column", gap: 8, maxWidth: 280 }}>
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            disabled={disabled}
            style={{ fontSize: TYPE.sm, color: t.textMuted, fontFamily: TYPE.fontBody }}
            onChange={(e) => {
              const f = e.target.files?.[0];
              onFileChange(f || null);
            }}
          />
          <span style={{ fontSize: TYPE.xs, color: t.textMuted, lineHeight: 1.45 }}>
            JPEG, PNG or Webp · Optional — use a clear headshot or keep the initials badge.
          </span>
          {file && (
            <Button type="button" variant="ghost" size="sm" disabled={disabled} onClick={() => onClearFile()}>
              Remove selected file
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}

/** Shared profile fields grid (theme-dark inputs). */
export function SuperUserProfileFields({ form, setForm, fieldErrors, disabled, mobileReadOnly = false }) {
  return (
    <div
      style={{
        display: "grid",
        gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))",
        gap: 16,
      }}
    >
      <Input
        label="First name *"
        value={form.first_name}
        onChange={(v) => setForm((p) => ({ ...p, first_name: v }))}
        hint={fieldErrors.first_name}
        autoComplete="given-name"
        disabled={disabled}
      />
      <Input
        label="Last name"
        value={form.last_name}
        onChange={(v) => setForm((p) => ({ ...p, last_name: v }))}
        autoComplete="family-name"
        disabled={disabled}
      />
      <Select label="Gender" value={form.gender} onChange={(v) => setForm((p) => ({ ...p, gender: v }))} options={GENDER_OPTIONS} disabled={disabled} />
      <Input
        label="Date of birth"
        type="date"
        value={form.date_of_birth}
        onChange={(v) => setForm((p) => ({ ...p, date_of_birth: v }))}
        disabled={disabled}
      />
      <Select
        label="Marital status"
        value={form.marital_status}
        onChange={(v) => setForm((p) => ({ ...p, marital_status: v }))}
        options={MARITAL_OPTIONS}
        disabled={disabled}
      />
      <Input
        label="Mobile *"
        value={form.mobile}
        onChange={(v) => setForm((p) => ({ ...p, mobile: v }))}
        hint={mobileReadOnly ? "Cannot be changed after the account is created." : fieldErrors.mobile}
        inputMode="tel"
        autoComplete="tel"
        disabled={disabled || mobileReadOnly}
      />
      <div style={{ gridColumn: "1 / -1" }}>
        <Input
          label="Email *"
          type="email"
          value={form.email}
          onChange={(v) => setForm((p) => ({ ...p, email: v }))}
          hint={fieldErrors.email}
          autoComplete="email"
          disabled={disabled}
        />
      </div>
    </div>
  );
}
