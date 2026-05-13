import React, { useState, useEffect, useCallback, useMemo } from "react";
import { useNavigate, useOutletContext } from "react-router-dom";
import { useAuth } from "../../auth/authContext";
import * as companyService from "../../company/companyService";
import * as lobService from "../../lineOfBusiness/lineOfBusinessService";
import { useTheme } from "../../theme";
import { TYPE } from "../../theme/typography";
import PageHeader from "../../components/PageHeader";
import Button from "../../components/Button";
import Card from "../../components/Card";
import Input from "../../components/Input";
import Select from "../../components/Select";
import Loader from "../../components/Loader";
import Modal from "../../components/Modal";
import MapPickerModal from "../../components/map/MapPickerModal";
import CompanyLogoAvatar from "../../components/CompanyLogoAvatar";
import ToggleSwitch from "../../components/ToggleSwitch";

const STEPS = [
  { id: 1, label: "Basic info" },
  { id: 2, label: "Branch & address" },
  { id: 3, label: "Bank info" },
  { id: 4, label: "Payment" },
  { id: 5, label: "Tax" },
  { id: 6, label: "Settings" },
  { id: 7, label: "Features" },
  { id: 8, label: "Review" },
];

const WEEK_DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

/** Mask account number for summary/detail (no external lib). */
function maskAccountNumber(s) {
  const d = String(s || "").replace(/\s/g, "");
  if (!d) return "—";
  if (d.length <= 4) return "•".repeat(d.length);
  return "•".repeat(d.length - 4) + d.slice(-4);
}

const defaultCompany = {
  company_name: "",
  legal_name: "",
  tag_line: "",
  description: "",
  phone_number: "",
  email: "",
  company_business_id: "",
  company_website: "",
  company_dawn: "",
  company_dusk: "",
  status: 1,
  customer_app: false,
  appointment_auto_confirm: false,
};

const defaultBank = {
  bank_name: "",
  bank_code: "",
  account_name: "",
  account_number: "",
};
const defaultBranch = { branch_status: 1, branch_type: "normal", work_type: "", head_branch: false, branch_name: "" };
const defaultContact = {
  phone: "",
  email: "",
  address1: "",
  area: "",
  city: "",
  state: "",
  country: "",
  pincode: "",
  latitude: "",
  longitude: "",
};

export default function CompanyWizardPage() {
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => { });
  const { tokens: t } = useTheme();

  const [step, setStep] = useState(1);
  const [company, setCompany] = useState({ ...defaultCompany });
  const [branches, setBranches] = useState([{ ...defaultBranch }]);
  const [contacts, setContacts] = useState([{ ...defaultContact }]);
  const [paymentMethodIds, setPaymentMethodIds] = useState([]);
  const [taxes, setTaxes] = useState([]);
  const [featureIds, setFeatureIds] = useState([]);
  const [paymentProviders, setPaymentProviders] = useState({});
  const [settingsForm, setSettingsForm] = useState({
    enforce_2fa: false,
    geo_fencing_enabled: false,
    geo_fencing_radius: "",
    appointment_time_slice_enabled: false,
    appointment_time_slice_minutes: "",
    auto_approve_appointments: false,
    marketing_message: "",
    public_company_page: false,
  });

  const [paymentOptions, setPaymentOptions] = useState([]);
  const [featureOptions, setFeatureOptions] = useState([]);
  const [dropdownsLoading, setDropdownsLoading] = useState(false);
  const [submitLoading, setSubmitLoading] = useState(false);
  const [pinModalOpen, setPinModalOpen] = useState(false);
  const [pin, setPin] = useState("");
  const [lobOptions, setLobOptions] = useState([]);
  const [lobOptionsLoading, setLobOptionsLoading] = useState(false);
  const [mapModalOpen, setMapModalOpen] = useState(false);
  const [mapTargetIndex, setMapTargetIndex] = useState(null);

  const [bank, setBank] = useState({ ...defaultBank });
  const [showBankAccount, setShowBankAccount] = useState(false);
  const [countries, setCountries] = useState([]);
  const [countriesLoading, setCountriesLoading] = useState(false);
  const [taxCountryIso, setTaxCountryIso] = useState("");
  const [templateOptions, setTemplateOptions] = useState([]);
  const [templatesLoading, setTemplatesLoading] = useState(false);
  const [selectedTemplateIds, setSelectedTemplateIds] = useState([]);
  const [applyAllCountryTaxTemplates, setApplyAllCountryTaxTemplates] = useState(false);
  const [businessHours, setBusinessHours] = useState(
    WEEK_DAYS.map((day, idx) => ({
      day_of_week: day,
      is_open: idx < 5,
      opening_time: "09:00",
      closing_time: "18:00",
      slot_index: 1,
    }))
  );

  /** Local preview only; logo is uploaded to disk after company is created. */
  const [logoPreview, setLogoPreview] = useState("");
  const [pendingLogoFile, setPendingLogoFile] = useState(null);

  const loadCountries = useCallback(async () => {
    if (!accessToken) return;
    setCountriesLoading(true);
    try {
      const res = await companyService.getCountries(accessToken);
      setCountries(res.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    } finally {
      setCountriesLoading(false);
    }
  }, [accessToken, addToastSafe]);

  const loadTaxTemplatesForCountry = useCallback(
    async (iso) => {
      if (!accessToken || !iso || String(iso).trim().length !== 2) {
        setTemplateOptions([]);
        return;
      }
      setTemplatesLoading(true);
      try {
        const res = await companyService.getTaxTemplates(accessToken, String(iso).trim().toUpperCase());
        setTemplateOptions(res.data || []);
      } catch (e) {
        addToastSafe("error", "Tax templates", e.message);
        setTemplateOptions([]);
      } finally {
        setTemplatesLoading(false);
      }
    },
    [accessToken, addToastSafe]
  );

  useEffect(() => {
    loadCountries();
  }, [loadCountries]);

  useEffect(() => {
    if (step !== 5) return;
    if (applyAllCountryTaxTemplates) return;
    loadTaxTemplatesForCountry(taxCountryIso);
  }, [step, taxCountryIso, applyAllCountryTaxTemplates, loadTaxTemplatesForCountry]);

  const loadDropdowns = useCallback(async () => {
    if (!accessToken) return;
    setDropdownsLoading(true);
    try {
      const [payRes, featRes] = await Promise.all([
        companyService.getPaymentMethods(accessToken),
        companyService.getFeatures(accessToken),
      ]);
      setPaymentOptions(payRes.data || []);
      setFeatureOptions(featRes.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    } finally {
      setDropdownsLoading(false);
    }
  }, [accessToken]);

  const loadLobs = useCallback(async () => {
    if (!accessToken) return;
    setLobOptionsLoading(true);
    try {
      const res = await lobService.getLineOfBusinessDropdowns(accessToken);
      setLobOptions(res.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    } finally {
      setLobOptionsLoading(false);
    }
  }, [accessToken]);

  useEffect(() => {
    loadLobs();
  }, [loadLobs]);

  useEffect(() => {
    if (step === 4 || step === 7) loadDropdowns();
  }, [step, loadDropdowns]);

  const isStep1Valid = () => {
    const nameOk = (company.company_name || "").trim().length > 0;
    const legalNameOk = (company.legal_name || "").trim().length > 0;
    const emailTrimmed = String(company.email || "").trim();
    const emailOk = emailTrimmed.length === 0 || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailTrimmed);
    const phone = String(company.phone_number || "").trim();
    const phoneOk = phone.length > 0 && /^\+?[0-9\s\-()]{7,32}$/.test(phone);
    const lobOk = String(company.company_business_id || "").trim().length > 0;
    const hasOpenDay = businessHours.some((row) => !!row.is_open);
    const scheduleOk = businessHours.every((row) => {
      if (!row.is_open) return true;
      return String(row.opening_time || "").trim().length > 0 && String(row.closing_time || "").trim().length > 0;
    });
    const website = (company.company_website || "").trim();
    let websiteOk = true;
    if (website.length > 0) {
      websiteOk = false;
      try {
        const withProtocol =
          /^https?:\/\//i.test(website) ? website : `https://${website}`;
        new URL(withProtocol);
        websiteOk = true;
      } catch (_) {
        websiteOk = false;
      }
    }
    return nameOk && legalNameOk && emailOk && phoneOk && lobOk && websiteOk && hasOpenDay && scheduleOk;
  };

  const headBranchIndex = useMemo(() => branches.findIndex((b) => !!b.head_branch), [branches]);

  // If a head branch is selected, propagate its country to all other branches.
  useEffect(() => {
    if (headBranchIndex < 0) return;
    const headCountry = String(contacts[headBranchIndex]?.country || "").trim();
    if (!headCountry) return;

    setContacts((prev) =>
      prev.map((c, i) => {
        if (i === headBranchIndex) return c;
        if (String(c.country || "").trim() === headCountry) return c;
        return { ...c, country: headCountry };
      })
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [headBranchIndex, contacts[headBranchIndex]?.country]);

  const canNext = () => {
    if (step === 1) return isStep1Valid();
    if (step === 2) return headBranchIndex >= 0;
    if (step === 3) return true;
    if (step === 4) return true;
    if (step === 5) return true;
    if (step === 6) return true;
    if (step === 7) return true;
    if (step === 8) return true;
    return true;
  };

  const updateBusinessHour = (idx, field, value) => {
    setBusinessHours((prev) => prev.map((row, i) => (i === idx ? { ...row, [field]: value } : row)));
  };

  const handleNext = () => {
    if (step < 8 && canNext()) setStep((s) => s + 1);
    if (step === 8) setPinModalOpen(true);
  };

  const handleBack = () => setStep((s) => Math.max(1, s - 1));

  const handleSubmit = async () => {
    if (!pin.trim() || !accessToken) {
      addToastSafe("error", "PIN required", "Enter your PIN to confirm.");
      return;
    }
    if (!isStep1Valid()) {
      addToastSafe(
        "error",
        "Company phone required",
        "Go back to step 1 and enter a valid company phone number before submitting."
      );
      return;
    }
    if (applyAllCountryTaxTemplates && (!taxCountryIso || String(taxCountryIso).trim().length !== 2)) {
      addToastSafe("error", "Tax country", "Select a country to apply all tax templates for that country.");
      return;
    }
    
    setSubmitLoading(true);
    try {

      const custApp = !!company.customer_app;
      const firstOpenSlot = businessHours.find((row) => row.is_open && row.opening_time && row.closing_time);
      const dawn = firstOpenSlot?.opening_time || "09:00";
      const dusk = firstOpenSlot?.closing_time || "18:00";
      const payload = {
        pin: pin.trim(),
        company: {
          company_name: company.company_name.trim(),
          legal_name: company.legal_name.trim(),
          tag_line: company.tag_line?.trim() || null,
          description: company.description?.trim() || null,
          phone_number: company.phone_number?.trim() || null,
          email: company.email?.trim() || null,
          company_business_id: company.company_business_id,
          company_website: company.company_website?.trim() || null,
          company_dawn: dawn,
          company_dusk: dusk,
          status: company.status,
          customer_app: custApp ? 1 : 0,
          appointment_auto_confirm: custApp && company.appointment_auto_confirm ? 1 : 0,
          bank_name: bank.bank_name?.trim() || null,
          bank_code: bank.bank_code?.trim() || null,
          account_name: bank.account_name?.trim() || null,
          account_number: bank.account_number?.trim() || null,
        },
        business_hours: businessHours.map((row) => ({
          day_of_week: row.day_of_week,
          is_open: !!row.is_open,
          opening_time: row.is_open ? row.opening_time : null,
          closing_time: row.is_open ? row.closing_time : null,
          slot_index: Number(row.slot_index || 1),
        })),

        branches: branches.map((b, idx) => ({
          branch_status: b.branch_status,
          head_branch: b.head_branch,
          branch_type: b.branch_type || null,
          work_type: b.work_type || null,
          branch_name: (b.branch_name || "").trim() || `Branch ${idx + 1}`,
          latitude: contacts[idx]?.latitude || null,
          longitude: contacts[idx]?.longitude || null,
        })),
        contacts: contacts.map((c) => ({
          phone: c.phone || null,
          email: c.email || null,
          address1: c.address1 || null,
          area: c.area || null,
          city: c.city || null,
          state: c.state || null,
          country: c.country || null,
          pincode: c.pincode || null,
          latitude: c.latitude || null,
          longitude: c.longitude || null,
        })),
        payment_method_ids: paymentMethodIds,
        payment_providers: paymentMethodIds
          .map((pid) => ({
            payment_id: pid,
            merchant_id: paymentProviders[pid]?.merchant_id || null,
            secret_key: paymentProviders[pid]?.secret_key || null,
          }))
          .filter((row) => row.merchant_id || row.secret_key),
        settings: {
          enforce_2fa: !!settingsForm.enforce_2fa,
          geo_fencing_enabled: !!settingsForm.geo_fencing_enabled,
          geo_fencing_radius: settingsForm.geo_fencing_enabled ? Number(settingsForm.geo_fencing_radius || 0) || null : null,
          appointment_time_slice_enabled: !!settingsForm.appointment_time_slice_enabled,
          appointment_time_slice_minutes: settingsForm.appointment_time_slice_enabled ? Number(settingsForm.appointment_time_slice_minutes || 0) || null : null,
          auto_approve_appointments: !!settingsForm.auto_approve_appointments,
          marketing_message: settingsForm.marketing_message || null,
          public_company_page: !!settingsForm.public_company_page,
        },
        taxes: taxes.length ? taxes : [],
        country_code:
          taxCountryIso && String(taxCountryIso).trim().length === 2 ? String(taxCountryIso).trim().toUpperCase() : undefined,
        selected_template_ids: applyAllCountryTaxTemplates ? [] : selectedTemplateIds,
        apply_all_country_tax_templates: applyAllCountryTaxTemplates,
        feature_ids: featureIds,
      };
      const res = await companyService.createCompany(payload, accessToken);
      const newId = res.data?.company_id;
      if (pendingLogoFile && newId) {
        try {
          await companyService.uploadCompanyLogo(String(newId), pendingLogoFile, accessToken);
        } catch (logoErr) {
          addToastSafe("error", "Logo upload failed", logoErr.message);
        }
      }
      if (logoPreview) URL.revokeObjectURL(logoPreview);
      setPinModalOpen(false);
      setPin("");
      addToastSafe("success", "Company created", res.data?.company_name || "Company created.");
      navigate(`/company/${newId}`, { replace: true });
    } catch (e) {
      addToastSafe("error", "Create failed", e.message);
    } finally {
      setSubmitLoading(false);
    }
  };

  const addBranchAndContact = () => {
    setBranches((b) => [...b, { ...defaultBranch }]);
    setContacts((c) => [...c, { ...defaultContact }]);
  };

  const removeBranch = (i) => {
    if (branches.length <= 1) return;
    if (branches[i]?.head_branch) {
      addToastSafe("error", "Head branch required", "Please mark another branch as Head Branch before removing this one.");
      return;
    }
    setBranches((b) => b.filter((_, j) => j !== i));
    setContacts((c) => c.filter((_, j) => j !== i));
  };

  const updateBranch = (i, field, value) => setBranches((b) => b.map((x, j) => (j === i ? { ...x, [field]: value } : x)));

  const updateContact = (i, field, value) => setContacts((c) => c.map((x, j) => (j === i ? { ...x, [field]: value } : x)));

  const setHeadBranch = (i) => {
    setBranches((prev) => prev.map((b, idx) => ({ ...b, head_branch: idx === i })));
  };

  const togglePayment = (id) => setPaymentMethodIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  const toggleFeature = (id) => setFeatureIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  const isCustomProvider = (name) => {
    const n = String(name || "").toLowerCase();
    return n.includes("stripe") || n.includes("razorpay") || n.includes("custom");
  };
  const updateProviderField = (paymentId, field, value) => {
    setPaymentProviders((prev) => ({
      ...prev,
      [paymentId]: { ...(prev[paymentId] || {}), [field]: value },
    }));
  };

  const handleLogoFile = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) {
      addToastSafe("error", "File too large", "Logo must be 2MB or less.");
      e.target.value = "";
      return;
    }
    const ok = ["image/png", "image/jpeg", "image/svg+xml"].includes(f.type);
    if (!ok) {
      addToastSafe("error", "Invalid type", "Use PNG, JPG, or SVG.");
      e.target.value = "";
      return;
    }
    if (logoPreview) URL.revokeObjectURL(logoPreview);
    setLogoPreview(URL.createObjectURL(f));
    setPendingLogoFile(f);
    e.target.value = "";
  };

  const countryOptions = (countries || []).map((row) => ({
    value: row.country_name,
    label: row.country_name,
  }));

  const taxCountrySelectOptions = [
    { value: "", label: countriesLoading ? "Loading…" : "Select country (ISO)…" },
    ...(countries || []).map((row) => ({
      value: String(row.country_code || "").toUpperCase(),
      label: `${row.country_name} (${row.country_code})`,
    })),
  ];

  const toggleTaxTemplateId = (id) => {
    setSelectedTemplateIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const addTax = () => setTaxes((t) => [...t, { tax_name: "", components: [{ component_name: "", details: [{ tax_value: 0, tax_start_date: new Date().toISOString().slice(0, 10), tax_end_date: "" }] }] }]);
  const removeTax = (i) => setTaxes((t) => t.filter((_, j) => j !== i));
  const updateTax = (i, field, value) => setTaxes((t) => t.map((x, j) => (j === i ? { ...x, [field]: value } : x)));

  const allFeaturesSelected = featureOptions.length > 0 && featureIds.length === featureOptions.length;
  const selectedPaymentOptions = paymentOptions.filter((pm) => paymentMethodIds.includes(pm.payment_id));
  const selectedFeatureOptions = featureOptions.filter((f) => featureIds.includes(f.feature_id));
  const nonEmptyTaxes = taxes.filter((tx) => String(tx?.tax_name || "").trim().length > 0);
  const lobLabel = lobOptions.find((o) => String(o?.lob_id) === String(company.company_business_id))?.lob_name || String(company.company_business_id || "");

  return (
    <div>
      <PageHeader title="New Company" subtitle="Create a tenant in 8 steps" breadcrumb="Companies → New" />
      <div style={{ display: "flex", gap: 24, marginBottom: 24, flexWrap: "wrap" }}>
        {STEPS.map((s) => (
          <button
            key={s.id}
            type="button"
            onClick={() => {
              if (s.id > 1 && !isStep1Valid()) {
                addToastSafe(
                  "error",
                  "Missing basic info",
                  "Fill company/legal name, phone number, line of business, and weekly business hours."
                );
                return;
              }
              if (s.id > 2 && headBranchIndex < 0) {
                addToastSafe("error", "Head branch required", "Select one branch as Head Branch in step 2 to continue.");
                return;
              }
              setStep(s.id);
            }}
            style={{
              padding: "8px 14px",
              borderRadius: 6,
              border: `1px solid ${step === s.id ? t.accent : t.border}`,
              background: step === s.id ? t.accentSubtle : "transparent",
              color: step === s.id ? t.accent : t.textSecondary,
              fontFamily: TYPE.fontBody,
              fontSize: TYPE.sm,
              cursor: "pointer",
            }}
          >
            {s.id}. {s.label}
          </button>
        ))}
      </div>

      <Card>
        {step === 1 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 16, maxWidth: 720 }}>
            <Input
              label="Company name"
              value={company.company_name}
              onChange={(v) => setCompany((c) => ({ ...c, company_name: v }))}
              placeholder="Acme Corp"
            />
            <Input
              label="Legal name"
              value={company.legal_name}
              onChange={(v) => setCompany((c) => ({ ...c, legal_name: v }))}
              placeholder="Acme Corporation Pvt. Ltd."
            />
            <Input
              label="Tag line"
              value={company.tag_line}
              onChange={(v) => setCompany((c) => ({ ...c, tag_line: v }))}
              placeholder="Built for scale"
            />

            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              <div>
                {lobOptionsLoading ? (
                  <div style={{ marginTop: 6, color: t.textMuted, fontSize: TYPE.sm }}>Loading line of business…</div>
                ) : (
                  <Select
                    label="Company line of business"
                    value={company.company_business_id}
                    onChange={(v) => setCompany((c) => ({ ...c, company_business_id: v }))}
                    options={(lobOptions || []).map((o) => ({ value: o.lob_id, label: o.lob_name }))}
                  />
                )}
              </div>

              <div>
                <Input
                  label="Company website"
                  value={company.company_website}
                  onChange={(v) => setCompany((c) => ({ ...c, company_website: v }))}
                  placeholder="https://example.com"
                />
              </div>
            </div>

            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              <Input
                label="Company email (optional)"
                value={company.email}
                onChange={(v) => setCompany((c) => ({ ...c, email: v }))}
                placeholder="hello@company.com"
              />
              <Input
                label="Phone number (required)"
                value={company.phone_number}
                onChange={(v) => setCompany((c) => ({ ...c, phone_number: v }))}
                placeholder="+1 555 000 1111"
              />
            </div>

            <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                Description
              </label>
              <textarea
                value={company.description}
                onChange={(e) => setCompany((c) => ({ ...c, description: e.target.value }))}
                rows={4}
                style={{
                  width: "100%",
                  borderRadius: 6,
                  background: t.bgElevated,
                  border: `1px solid ${t.border}`,
                  color: t.text,
                  fontFamily: TYPE.fontBody,
                  fontSize: TYPE.base,
                  padding: 12,
                }}
                placeholder="Brief public profile of the company..."
              />
            </div>

            <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                Weekly business hours
              </label>
              {businessHours.map((row, idx) => (
                <div
                  key={row.day_of_week}
                  style={{
                    border: `1px solid ${t.border}`,
                    borderRadius: 8,
                    padding: 10,
                    display: "grid",
                    gridTemplateColumns: "140px 90px 1fr 1fr",
                    gap: 10,
                    alignItems: "center",
                  }}
                >
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{row.day_of_week}</div>
                  <div style={{ display: "inline-flex", alignItems: "center", gap: 8, color: t.textSecondary, fontSize: TYPE.sm }}>
                    <ToggleSwitch
                      checked={!!row.is_open}
                      onChange={(next) => updateBusinessHour(idx, "is_open", next)}
                      ariaLabel={`${row.day_of_week} open status`}
                    />
                    Open
                  </div>
                  <Input
                    label="Opening"
                    type="time"
                    value={row.opening_time}
                    onChange={(v) => updateBusinessHour(idx, "opening_time", v)}
                    disabled={!row.is_open}
                  />
                  <Input
                    label="Closing"
                    type="time"
                    value={row.closing_time}
                    onChange={(v) => updateBusinessHour(idx, "closing_time", v)}
                    disabled={!row.is_open}
                  />
                </div>
              ))}
              <div style={{ fontSize: TYPE.xs, color: t.textMuted }}>
                Supports open/closed per weekday and future multi-slot enhancements.
              </div>
            </div>

            <div>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, display: "block", marginBottom: 6 }}>Status</label>
              <select
                value={company.status}
                onChange={(e) => setCompany((c) => ({ ...c, status: Number(e.target.value) }))}
                style={{ width: "100%", height: 38, padding: "0 12px", background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text, fontFamily: TYPE.fontBody }}
              >
                <option value={1}>Active</option>
                <option value={0}>Inactive</option>
              </select>
            </div>

            <div style={{ display: "flex", alignItems: "flex-start", gap: 16, flexWrap: "wrap" }}>
              <CompanyLogoAvatar name={company.company_name} logoUrl={logoPreview} size={72} />
              <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                <span style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                  Company logo
                </span>
                <input type="file" accept="image/png,image/jpeg,image/svg+xml" onChange={handleLogoFile} style={{ fontSize: TYPE.sm, color: t.textSecondary }} />
                {pendingLogoFile ? (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      if (logoPreview) URL.revokeObjectURL(logoPreview);
                      setLogoPreview("");
                      setPendingLogoFile(null);
                    }}
                  >
                    Remove logo
                  </Button>
                ) : null}
                <span style={{ fontSize: TYPE.xs, color: t.textMuted }}>PNG, JPG, or SVG — max 2MB. If empty, initials are used. Uploaded to server after company is created.</span>
              </div>
            </div>

            <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
              <label
                style={{
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                  gap: 12,
                  cursor: "pointer",
                  userSelect: "none",
                }}
              >
                <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Enable Customer App</span>
                <ToggleSwitch
                  checked={!!company.customer_app}
                  onChange={(next) => {
                    setCompany((c) => ({ ...c, customer_app: next, appointment_auto_confirm: next ? c.appointment_auto_confirm : false }));
                  }}
                  ariaLabel="Enable customer app"
                />
              </label>

              <label
                style={{
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                  gap: 12,
                  cursor: company.customer_app ? "pointer" : "not-allowed",
                  userSelect: "none",
                  opacity: company.customer_app ? 1 : 0.5,
                }}
              >
                <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Auto-confirm appointments</span>
                <ToggleSwitch
                  checked={!!company.appointment_auto_confirm}
                  disabled={!company.customer_app}
                  onChange={(next) => {
                    if (!company.customer_app) return;
                    setCompany((c) => ({ ...c, appointment_auto_confirm: next }));
                  }}
                  ariaLabel="Auto confirm appointments"
                />
              </label>
              {!company.customer_app ? (
                <span style={{ fontSize: TYPE.xs, color: t.textMuted }}>Turn on Customer App to enable auto-confirm.</span>
              ) : null}
            </div>
          </div>
        )}

        {step === 2 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
            {branches.map((b, i) => {
              const countryReady = String(contacts[i]?.country || "").trim().length > 0;
              return (
              <div
                key={i}
                style={{
                  padding: 16,
                  border: `1px solid ${t.border}`,
                  borderRadius: 8,
                  display: "flex",
                  flexDirection: "column",
                  gap: 14,
                }}
              >
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: 12 }}>
                  <div style={{ display: "flex", gap: 12, alignItems: "center" }}>
                    <div
                      style={{
                        width: 34,
                        height: 34,
                        borderRadius: 10,
                        background: b.head_branch ? t.accentSubtle : t.bgElevated,
                        border: `1px solid ${b.head_branch ? t.accent : t.border}`,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontFamily: TYPE.fontMono,
                        color: b.head_branch ? t.accent : t.textSecondary,
                        fontWeight: TYPE.bold,
                      }}
                    >
                      {i + 1}
                    </div>
                    <div>
                      <div style={{ fontWeight: TYPE.semibold, color: t.text }}>{b.head_branch ? "Head Branch" : "Branch"}</div>
                      <div style={{ fontSize: TYPE.xs, color: t.textMuted, fontFamily: TYPE.fontMono }}>{b.branch_type || "normal"}</div>
                    </div>
                  </div>

                  <div style={{ display: "flex", gap: 12, alignItems: "center" }}>
                    <label style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
                      <input
                        type="radio"
                        name="head-branch"
                        checked={!!b.head_branch}
                        onChange={() => setHeadBranch(i)}
                        style={{ accentColor: t.accent }}
                      />
                      <span style={{ color: t.textSecondary, fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>Head</span>
                    </label>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => removeBranch(i)}
                      disabled={branches.length <= 1 || !!b.head_branch}
                    >
                      Remove
                    </Button>
                  </div>
                </div>

                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
                  <Input
                    label="Branch name"
                    value={b.branch_name || ""}
                    onChange={(v) => updateBranch(i, "branch_name", v)}
                    placeholder={`Branch ${i + 1}`}
                    style={{ gridColumn: "1 / -1" }}
                  />
                  <div>
                    <label style={{ fontSize: TYPE.xs, color: t.textMuted, display: "block", marginBottom: 6, textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: TYPE.bold }}>
                      Branch status
                    </label>
                    <select
                      value={b.branch_status}
                      onChange={(e) => updateBranch(i, "branch_status", Number(e.target.value))}
                      style={{ width: "100%", height: 38, padding: "0 12px", background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text }}
                    >
                      <option value={1}>Active</option>
                      <option value={0}>Inactive</option>
                    </select>
                  </div>
                  <div>
                    <label style={{ fontSize: TYPE.xs, color: t.textMuted, display: "block", marginBottom: 6, textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: TYPE.bold }}>
                      Branch type
                    </label>
                    <select
                      value={b.branch_type}
                      onChange={(e) => updateBranch(i, "branch_type", e.target.value)}
                      style={{ width: "100%", height: 38, padding: "0 12px", background: t.bgElevated, border: `1px solid ${t.border}`, borderRadius: 6, color: t.text }}
                    >
                      <option value="normal">Normal branch</option>
                      <option value="warehouse">Warehouse</option>
                      <option value="other">Others</option>
                    </select>
                  </div>
                </div>

                <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
                  {countriesLoading ? (
                    <div style={{ color: t.textMuted, fontSize: TYPE.sm }}>Loading countries…</div>
                  ) : (
                    <Select
                      label="Country"
                      value={contacts[i].country || ""}
                      onChange={(v) => {
                        if (!b.head_branch) return;
                        updateContact(i, "country", v);
                      }}
                      disabled={!b.head_branch}
                      options={[{ value: "", label: "Select country…" }, ...countryOptions]}
                    />
                  )}
                  {!countryReady ? (
                    <div style={{ fontSize: TYPE.xs, color: t.textMuted }}>Select a country to enter address details.</div>
                  ) : null}
                </div>

                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
                  <Input label="Phone" value={contacts[i].phone} onChange={(v) => updateContact(i, "phone", v)} disabled={!countryReady} />
                  <Input label="Email" value={contacts[i].email} onChange={(v) => updateContact(i, "email", v)} placeholder="name@domain.com" disabled={!countryReady} />
                  <Input label="Address line" value={contacts[i].address1} onChange={(v) => updateContact(i, "address1", v)} style={{ gridColumn: "1 / -1" }} disabled={!countryReady} />
                  <Input label="Area" value={contacts[i].area} onChange={(v) => updateContact(i, "area", v)} disabled={!countryReady} />
                  <Input label="City" value={contacts[i].city} onChange={(v) => updateContact(i, "city", v)} disabled={!countryReady} />
                  <Input label="State / province" value={contacts[i].state} onChange={(v) => updateContact(i, "state", v)} disabled={!countryReady} />
                  <Input label="Postal code" value={contacts[i].pincode} onChange={(v) => updateContact(i, "pincode", v)} disabled={!countryReady} />

                  <Input label="Latitude" value={contacts[i].latitude} onChange={(v) => updateContact(i, "latitude", v)} disabled={!countryReady} />
                  <Input label="Longitude" value={contacts[i].longitude} onChange={(v) => updateContact(i, "longitude", v)} disabled={!countryReady} />

                  <div style={{ gridColumn: "1 / -1", display: "flex", justifyContent: "flex-end" }}>
                    <Button
                      variant="dark"
                      icon="🗺"
                      disabled={!countryReady}
                      onClick={() => {
                        setMapTargetIndex(i);
                        setMapModalOpen(true);
                      }}
                    >
                      Pick on map
                    </Button>
                  </div>
                </div>
              </div>
              );
            })}

            <Button variant="dark" icon="+" onClick={addBranchAndContact}>
              Add branch & address
            </Button>

            {headBranchIndex < 0 && (
              <div style={{ color: t.danger, fontSize: TYPE.sm, fontFamily: TYPE.fontBody }}>
                Head Branch is mandatory. Mark one branch as Head Branch to continue.
              </div>
            )}
          </div>
        )}

        {step === 3 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 14, maxWidth: 720 }}>
            <p style={{ fontSize: TYPE.sm, color: t.textMuted, margin: 0 }}>Bank details are optional unless required by your process.</p>
            <Input label="Bank name" value={bank.bank_name} onChange={(v) => setBank((prev) => ({ ...prev, bank_name: v }))} />
            <Input label="Bank code" value={bank.bank_code} onChange={(v) => setBank((prev) => ({ ...prev, bank_code: v }))} />
            <Input label="Account name" value={bank.account_name} onChange={(v) => setBank((prev) => ({ ...prev, account_name: v }))} />
            <div>
              <Input
                label="Account number"
                type={showBankAccount ? "text" : "password"}
                value={bank.account_number}
                onChange={(v) => setBank((prev) => ({ ...prev, account_number: v }))}
              />
              <label style={{ display: "inline-flex", alignItems: "center", gap: 8, marginTop: 8, cursor: "pointer" }}>
                <input type="checkbox" checked={showBankAccount} onChange={(e) => setShowBankAccount(e.target.checked)} />
                <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Show account number</span>
              </label>
            </div>
          </div>
        )}

        {step === 4 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            {dropdownsLoading ? (
              <Loader />
            ) : (
              <>
                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary }}>Payment methods</label>
                <div style={{ display: "flex", flexDirection: "column", gap: 0, border: `1px solid ${t.border}`, borderRadius: 10, overflow: "hidden", background: t.bgElevated, marginTop: 10 }}>
                  {paymentOptions.map((pm) => {
                    const checked = paymentMethodIds.includes(pm.payment_id);
                    const customProvider = isCustomProvider(pm.payment_name);
                    const providerState = paymentProviders[pm.payment_id] || {};
                    return (
                      <div key={pm.payment_id} style={{ borderBottom: `1px solid ${t.border}` }}>
                        <div
                          style={{
                            display: "grid",
                            gridTemplateColumns: "1fr 160px",
                            alignItems: "center",
                            padding: "12px 14px",
                          }}
                        >
                          <div style={{ minWidth: 0 }}>
                            <div style={{ color: t.text, fontWeight: TYPE.semibold, fontFamily: TYPE.fontBody, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                              {pm.payment_name}
                            </div>
                            {pm.payment_description && (
                              <div style={{ color: t.textMuted, fontSize: TYPE.xs, marginTop: 4, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                                {pm.payment_description}
                              </div>
                            )}
                          </div>
                          <div style={{ display: "flex", justifyContent: "flex-end" }}>
                            <ToggleSwitch
                              checked={checked}
                              onChange={() => togglePayment(pm.payment_id)}
                              ariaLabel={`${pm.payment_name} payment toggle`}
                            />
                          </div>
                        </div>

                        {checked && customProvider ? (
                          <div style={{ padding: "0 14px 12px 14px", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                            <Input
                              label="Merchant ID"
                              value={providerState.merchant_id || ""}
                              onChange={(v) => updateProviderField(pm.payment_id, "merchant_id", v)}
                              placeholder="Enter merchant ID"
                            />
                            <Input
                              label="Secret Key"
                              type="password"
                              value={providerState.secret_key || ""}
                              onChange={(v) => updateProviderField(pm.payment_id, "secret_key", v)}
                              placeholder="Enter secret key"
                            />
                          </div>
                        ) : null}
                      </div>
                    );
                  })}
                </div>
              </>
            )}
          </div>
        )}

        {step === 5 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 8, background: t.bgElevated }}>
              <div style={{ fontWeight: TYPE.semibold, color: t.text, marginBottom: 8 }}>Country tax templates</div>
              <p style={{ fontSize: TYPE.sm, color: t.textMuted, margin: "0 0 12px 0" }}>
                Pick a country and apply admin-defined templates (cloned into this company). Optional: add manual tax rows below.
              </p>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
                <Select label="Country (ISO)" value={taxCountryIso} onChange={setTaxCountryIso} options={taxCountrySelectOptions} />
                <label style={{ display: "flex", alignItems: "center", gap: 10, marginTop: 22, color: t.text }}>
                  <input
                    type="checkbox"
                    checked={applyAllCountryTaxTemplates}
                    onChange={(e) => {
                      setApplyAllCountryTaxTemplates(e.target.checked);
                      if (e.target.checked) setSelectedTemplateIds([]);
                    }}
                  />
                  Apply all active templates for this country
                </label>
              </div>
              {!applyAllCountryTaxTemplates && (
                <div style={{ marginTop: 12 }}>
                  {templatesLoading ? (
                    <div style={{ color: t.textMuted, fontSize: TYPE.sm }}>Loading templates…</div>
                  ) : templateOptions.length === 0 ? (
                    <div style={{ color: t.textMuted, fontSize: TYPE.sm }}>{taxCountryIso ? "No templates for this country." : "Select a country to list templates."}</div>
                  ) : (
                    <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                      {templateOptions.map((tm) => (
                        <label
                          key={tm.template_tax_id || tm.tax_id}
                          style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", color: t.text }}
                        >
                          <input
                            type="checkbox"
                            checked={selectedTemplateIds.includes(tm.template_tax_id || tm.tax_id)}
                            onChange={() => toggleTaxTemplateId(tm.template_tax_id || tm.tax_id)}
                          />
                          <span>
                            {tm.tax_name}
                            {tm.country_code ? (
                              <span style={{ color: t.textMuted, fontSize: TYPE.xs }}> — {tm.country_code}</span>
                            ) : null}
                          </span>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>

            <p style={{ fontSize: TYPE.sm, color: t.textMuted }}>Add manual tax structures (name and optional components). Leave empty if templates cover your needs.</p>
            {taxes.map((tax, i) => (
              <div key={i} style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 8 }}>
                <div style={{ display: "flex", gap: 12, alignItems: "center", marginBottom: 8 }}>
                  <Input label="Tax name" value={tax.tax_name} onChange={(v) => updateTax(i, "tax_name", v)} placeholder="e.g. GST" style={{ flex: 1 }} />
                  <Button variant="ghost" size="sm" onClick={() => removeTax(i)}>Remove</Button>
                </div>
              </div>
            ))}
            <Button variant="dark" icon="+" onClick={addTax}>Add tax</Button>
          </div>
        )}

        {step === 6 && (
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
            {[
              ["enforce_2fa", "Enforce 2FA"],
              ["geo_fencing_enabled", "Geo fencing enabled"],
              ["appointment_time_slice_enabled", "Appointment time slice enabled"],
              ["auto_approve_appointments", "Auto approve appointments"],
              ["public_company_page", "Public company page"],
            ].map(([key, label]) => (
              <div key={key} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", border: `1px solid ${t.border}`, borderRadius: 8, padding: "10px 12px", color: t.textSecondary }}>
                <span>{label}</span>
                <ToggleSwitch
                  checked={!!settingsForm[key]}
                  onChange={(next) => setSettingsForm((prev) => ({ ...prev, [key]: next }))}
                  ariaLabel={label}
                />
              </div>
            ))}
            <Input
              label="Geo fencing radius (meters)"
              value={settingsForm.geo_fencing_radius}
              onChange={(v) => setSettingsForm((prev) => ({ ...prev, geo_fencing_radius: v }))}
              disabled={!settingsForm.geo_fencing_enabled}
            />
            <Input
              label="Appointment time slice (minutes)"
              value={settingsForm.appointment_time_slice_minutes}
              onChange={(v) => setSettingsForm((prev) => ({ ...prev, appointment_time_slice_minutes: v }))}
              disabled={!settingsForm.appointment_time_slice_enabled}
            />
            <div style={{ gridColumn: "1 / -1", display: "flex", flexDirection: "column", gap: 6 }}>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>Marketing message</label>
              <textarea
                value={settingsForm.marketing_message}
                onChange={(e) => setSettingsForm((prev) => ({ ...prev, marketing_message: e.target.value }))}
                rows={4}
                style={{
                  width: "100%",
                  borderRadius: 6,
                  background: t.bgElevated,
                  border: `1px solid ${t.border}`,
                  color: t.text,
                  fontFamily: TYPE.fontBody,
                  fontSize: TYPE.base,
                  padding: 12,
                }}
              />
            </div>
          </div>
        )}

        {step === 7 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            {dropdownsLoading ? (
              <Loader />
            ) : (
              <>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: 12 }}>
                  <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary }}>Features</label>

                  <label
                    style={{
                      display: "inline-flex",
                      alignItems: "center",
                      gap: 10,
                      cursor: "pointer",
                      userSelect: "none",
                      fontFamily: TYPE.fontBody,
                      fontSize: TYPE.sm,
                      color: t.textSecondary,
                    }}
                  >
                    <span>All</span>
                    <input
                      type="checkbox"
                      checked={allFeaturesSelected}
                      onChange={() => {
                        setFeatureIds(allFeaturesSelected ? [] : featureOptions.map((f) => f.feature_id));
                      }}
                      style={{ display: "none" }}
                    />
                    <span
                      style={{
                        width: 46,
                        height: 24,
                        borderRadius: 999,
                        background: allFeaturesSelected ? t.accent : t.bgElevated,
                        border: `1px solid ${allFeaturesSelected ? t.accent : t.border}`,
                        position: "relative",
                        transition: "all 0.15s ease",
                      }}
                    >
                      <span
                        style={{
                          position: "absolute",
                          top: 3,
                          left: allFeaturesSelected ? 23 : 3,
                          width: 18,
                          height: 18,
                          borderRadius: "50%",
                          background: allFeaturesSelected ? "#000" : t.textMuted,
                          transition: "all 0.15s ease",
                          boxShadow: allFeaturesSelected ? "0 8px 20px rgba(250,204,21,0.25)" : "none",
                        }}
                      />
                    </span>
                  </label>
                </div>

                <div style={{ display: "flex", flexDirection: "column", gap: 0, border: `1px solid ${t.border}`, borderRadius: 10, overflow: "hidden", background: t.bgElevated, marginTop: 10 }}>
                  {featureOptions.map((f) => {
                    const checked = featureIds.includes(f.feature_id);
                    return (
                      <div
                        key={f.feature_id}
                        style={{
                          display: "grid",
                          gridTemplateColumns: "1fr 160px",
                          alignItems: "center",
                          padding: "12px 14px",
                          borderBottom: `1px solid ${t.border}`,
                        }}
                      >
                        <div style={{ minWidth: 0 }}>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold, fontFamily: TYPE.fontBody, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                            {f.feature_name}
                          </div>
                          {f.feature_description && (
                            <div style={{ color: t.textMuted, fontSize: TYPE.xs, marginTop: 4, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                              {f.feature_description}
                            </div>
                          )}
                        </div>

                        <div style={{ display: "flex", justifyContent: "flex-end" }}>
                          <label
                            style={{
                              display: "inline-flex",
                              alignItems: "center",
                              cursor: "pointer",
                              userSelect: "none",
                            }}
                          >
                            <input
                              type="checkbox"
                              checked={checked}
                              onChange={() => toggleFeature(f.feature_id)}
                              style={{ display: "none" }}
                            />
                            <span
                              style={{
                                width: 46,
                                height: 24,
                                borderRadius: 999,
                                background: checked ? t.accent : t.bgElevated,
                                border: `1px solid ${checked ? t.accent : t.border}`,
                                position: "relative",
                                transition: "all 0.15s ease",
                              }}
                            >
                              <span
                                style={{
                                  position: "absolute",
                                  top: 3,
                                  left: checked ? 23 : 3,
                                  width: 18,
                                  height: 18,
                                  borderRadius: "50%",
                                  background: checked ? "#000" : t.textMuted,
                                  transition: "all 0.15s ease",
                                  boxShadow: checked ? "0 8px 20px rgba(250,204,21,0.25)" : "none",
                                }}
                              />
                            </span>
                          </label>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </>
            )}
          </div>
        )}

        {step === 8 && (
          <div style={{ display: "flex", flexDirection: "column", gap: 14, fontFamily: TYPE.fontBody, color: t.textSecondary, fontSize: TYPE.sm }}>
            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Basic info</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(1)}>Edit</Button>
              </div>
              <div style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: 12 }}>
                <CompanyLogoAvatar name={company.company_name} logoUrl={logoPreview} size={64} />
                <div style={{ fontSize: TYPE.xs, color: t.textMuted }}>Logo preview</div>
              </div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Name</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.company_name || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Line of business</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{lobLabel || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Legal name</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.legal_name || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Email</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.email || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Website</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                    {company.company_website || "—"}
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Status</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.status ? "Active" : "Inactive"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Customer app</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.customer_app ? "On" : "Off"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Auto-confirm appointments</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{company.customer_app && company.appointment_auto_confirm ? "On" : "Off"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Open days</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{businessHours.filter((r) => r.is_open).length}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Closed days</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{businessHours.filter((r) => !r.is_open).length}</div>
                </div>
              </div>
            </div>

            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Branches & address</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(2)}>Edit</Button>
              </div>
              <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
                {branches.map((b, i) => {
                  const c = contacts[i] || {};
                  return (
                    <div key={i} style={{ padding: 14, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
                      <div style={{ display: "flex", justifyContent: "space-between", gap: 12, alignItems: "center", marginBottom: 10 }}>
                        <div style={{ color: t.text, fontWeight: TYPE.semibold }}>
                          {i + 1}. {b.head_branch ? "Head Branch" : "Branch"} ({b.branch_type || "normal"})
                        </div>
                        <div style={{ color: t.textSecondary }}>{b.branch_status ? "Active" : "Inactive"}</div>
                      </div>

                      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                        <div style={{ gridColumn: "1 / -1" }}>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Country</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.country || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Phone</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.phone || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Email</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.email || "—"}</div>
                        </div>
                        <div style={{ gridColumn: "1 / -1" }}>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Address</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.address1 || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Area</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.area || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>City</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.city || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>State</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.state || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Postal code</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{c.pincode || "—"}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Lat/Lng</div>
                          <div style={{ color: t.text, fontWeight: TYPE.semibold }}>
                            {c.latitude || "—"} / {c.longitude || "—"}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Bank</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(3)}>Edit</Button>
              </div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Bank name</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{bank.bank_name?.trim() || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Bank code</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{bank.bank_code?.trim() || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Account name</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{bank.account_name?.trim() || "—"}</div>
                </div>
                <div>
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginBottom: 4 }}>Account number</div>
                  <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{maskAccountNumber(bank.account_number)}</div>
                </div>
              </div>
            </div>

            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Payment methods</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(4)}>Edit</Button>
              </div>
              {selectedPaymentOptions.length ? (
                <div style={{ display: "flex", flexWrap: "wrap", gap: 8 }}>
                  {selectedPaymentOptions.map((pm) => (
                    <span
                      key={pm.payment_id}
                      style={{
                        padding: "8px 12px",
                        borderRadius: 999,
                        border: `1px solid ${t.border}`,
                        background: t.bgElevated,
                        color: t.text,
                        fontWeight: TYPE.semibold,
                        fontSize: TYPE.sm,
                      }}
                    >
                      {pm.payment_name}
                    </span>
                  ))}
                </div>
              ) : (
                <div style={{ color: t.textMuted }}>None selected</div>
              )}
            </div>

              <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Taxes</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(5)}>Edit</Button>
              </div>
              {applyAllCountryTaxTemplates && taxCountryIso ? (
                <div style={{ fontSize: TYPE.sm, color: t.textMuted, marginBottom: 10 }}>
                  Templates: all active for {String(taxCountryIso).toUpperCase()}
                </div>
              ) : null}
              {!applyAllCountryTaxTemplates && selectedTemplateIds.length > 0 ? (
                <div style={{ fontSize: TYPE.sm, color: t.textMuted, marginBottom: 10 }}>
                  Templates: {selectedTemplateIds.length} selected
                  {taxCountryIso ? ` (${String(taxCountryIso).toUpperCase()})` : ""}
                </div>
              ) : null}
              {nonEmptyTaxes.length ? (
                <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
                  {nonEmptyTaxes.map((tx, i) => (
                    <div key={i} style={{ padding: 12, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
                      <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{tx.tax_name}</div>
                      <div style={{ color: t.textMuted, fontSize: TYPE.xs, marginTop: 6 }}>
                        Components: {Array.isArray(tx.components) ? tx.components.length : 0}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div style={{ color: t.textMuted }}>No taxes added</div>
              )}
            </div>

            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Features</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(7)}>Edit</Button>
              </div>
              {selectedFeatureOptions.length ? (
                <div style={{ display: "flex", flexWrap: "wrap", gap: 8 }}>
                  {selectedFeatureOptions.map((f) => (
                    <span
                      key={f.feature_id}
                      style={{
                        padding: "8px 12px",
                        borderRadius: 999,
                        border: `1px solid ${t.border}`,
                        background: t.bgElevated,
                        color: t.text,
                        fontWeight: TYPE.semibold,
                        fontSize: TYPE.sm,
                      }}
                    >
                      {f.feature_name}
                    </span>
                  ))}
                </div>
              ) : (
                <div style={{ color: t.textMuted }}>None selected</div>
              )}
            </div>

            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 10, background: t.bgElevated }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
                <div style={{ fontWeight: TYPE.semibold, color: t.text }}>Settings</div>
                <Button variant="ghost" size="sm" onClick={() => setStep(6)}>Edit</Button>
              </div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 8 }}>
                <div style={{ color: t.textSecondary }}>Enforce 2FA: <strong style={{ color: t.text }}>{settingsForm.enforce_2fa ? "On" : "Off"}</strong></div>
                <div style={{ color: t.textSecondary }}>Geo fencing: <strong style={{ color: t.text }}>{settingsForm.geo_fencing_enabled ? "On" : "Off"}</strong></div>
                <div style={{ color: t.textSecondary }}>Public page: <strong style={{ color: t.text }}>{settingsForm.public_company_page ? "On" : "Off"}</strong></div>
              </div>
            </div>

            <div style={{ marginTop: 4, color: t.textMuted, fontSize: TYPE.xs }}>
              Confirm everything above, then click <strong style={{ color: t.accent }}>Confirm & enter PIN</strong> to create the company.
            </div>
          </div>
        )}

        <div style={{ display: "flex", justifyContent: "space-between", marginTop: 24, paddingTop: 16, borderTop: `1px solid ${t.border}` }}>
          <Button variant="ghost" onClick={handleBack} disabled={step <= 1}>Back</Button>
          <Button variant="primary" onClick={handleNext} disabled={step === 1 && !canNext()}>
            {step === 8 ? "Confirm & enter PIN" : "Next"}
          </Button>
        </div>
      </Card>

      <Modal open={pinModalOpen} onClose={() => { if (!submitLoading) { setPinModalOpen(false); setPin(""); } }} title="Enter your PIN to confirm">
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          <Input label="PIN" type="password" value={pin} onChange={setPin} placeholder="Your admin PIN" />
          <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
            <Button variant="ghost" onClick={() => { setPinModalOpen(false); setPin(""); }} disabled={submitLoading}>Cancel</Button>
            <Button variant="primary" onClick={handleSubmit} disabled={submitLoading || !pin.trim()}>
              {submitLoading ? "Creating…" : "Create company"}
            </Button>
          </div>
        </div>
      </Modal>

      <MapPickerModal
        open={mapModalOpen}
        onClose={() => setMapModalOpen(false)}
        initialLat={Number(contacts[mapTargetIndex]?.latitude) || 12.9716}
        initialLng={Number(contacts[mapTargetIndex]?.longitude) || 77.5946}
        onPick={(lat, lng) => {
          if (mapTargetIndex == null) return;
          updateContact(mapTargetIndex, "latitude", String(lat));
          updateContact(mapTargetIndex, "longitude", String(lng));
        }}
      />
    </div>
  );
}
