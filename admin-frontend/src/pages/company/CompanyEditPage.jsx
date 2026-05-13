import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useOutletContext, useParams } from "react-router-dom";
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
import Modal from "../../components/Modal";
import TextSkeleton from "../../components/skeleton/TextSkeleton";
import CompanyLogoAvatar from "../../components/CompanyLogoAvatar";
import Loader from "../../components/Loader";
import MapPickerModal from "../../components/map/MapPickerModal";
import ToggleSwitch from "../../components/ToggleSwitch";

function maskAccountNumber(s) {
  const d = String(s || "").replace(/\s/g, "");
  if (!d) return "";
  if (d.length <= 4) return "•".repeat(d.length);
  return "•".repeat(d.length - 4) + d.slice(-4);
}

const defaultBranch = {
  branch_id: null,
  branch_status: 1,
  branch_type: "normal",
  head_branch: false,
  branch_name: "",
  latitude: "",
  longitude: "",
};
const defaultContact = {
  contact_id: null,
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

function mapBranchFromApi(b) {
  const wt = Number(b.work_type);
  let branchType = "normal";
  if (wt === 2) branchType = "warehouse";
  else if (wt === 3) branchType = "retail";
  else if (String(b.branch_type || "").toLowerCase() === "other") branchType = "other";
  const head = String(b.branch_type || "").toUpperCase() === "H";
  return {
    branch_id: b.branch_id,
    branch_status: b.branch_status ?? 1,
    branch_type: branchType,
    head_branch: head,
    branch_name: b.branch_name || "",
    latitude: b.latitude != null ? String(b.latitude) : "",
    longitude: b.longitude != null ? String(b.longitude) : "",
  };
}

function mapContactFromApi(c) {
  if (!c) return { ...defaultContact };
  return {
    contact_id: c.contact_id,
    phone: c.phone || "",
    email: c.email || "",
    address1: c.address1 || "",
    area: c.area || "",
    city: c.city || "",
    state: c.state || "",
    country: c.country || "",
    pincode: c.pincode || "",
    latitude: c.latitude != null ? String(c.latitude) : "",
    longitude: c.longitude != null ? String(c.longitude) : "",
  };
}

const TABS = [
  { id: "basic", label: "Basic info" },
  { id: "bank", label: "Bank info" },
  { id: "branches", label: "Branches & address" },
  { id: "hours", label: "Business hours" },
  { id: "settings", label: "Settings" },
  { id: "payment", label: "Payment methods" },
  { id: "tax", label: "Tax" },
  { id: "features", label: "Features" },
];

export default function CompanyEditPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { accessToken } = useAuth();
  const { addToast } = useOutletContext() || {};
  const addToastSafe = addToast || (() => {});
  const { tokens: t } = useTheme();

  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState("basic");
  const [submitting, setSubmitting] = useState(false);

  const [lobOptions, setLobOptions] = useState([]);
  const [lobLoading, setLobLoading] = useState(false);

  const [form, setForm] = useState({
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
    company_status: 1,
    customer_app: false,
    appointment_auto_confirm: false,
    bank_name: "",
    bank_code: "",
    account_name: "",
    account_number: "",
  });

  const [logoKey, setLogoKey] = useState("");
  const [logoUrls, setLogoUrls] = useState(null);
  const [logoUploading, setLogoUploading] = useState(false);

  const [branches, setBranches] = useState([{ ...defaultBranch }]);
  const [contacts, setContacts] = useState([{ ...defaultContact }]);
  const [countries, setCountries] = useState([]);
  const [countriesLoading, setCountriesLoading] = useState(false);

  const [paymentOptions, setPaymentOptions] = useState([]);
  const [featureOptions, setFeatureOptions] = useState([]);
  const [paymentMethodIds, setPaymentMethodIds] = useState([]);
  const [paymentProviders, setPaymentProviders] = useState({});
  const [featureIds, setFeatureIds] = useState([]);
  const [taxes, setTaxes] = useState([]);
  const [taxCountryIso, setTaxCountryIso] = useState("");
  const [templateOptions, setTemplateOptions] = useState([]);
  const [templatesLoading, setTemplatesLoading] = useState(false);
  const [selectedTemplateIds, setSelectedTemplateIds] = useState([]);
  const [applyAllCountryTaxTemplates, setApplyAllCountryTaxTemplates] = useState(false);
  const [businessHours, setBusinessHours] = useState([]);
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

  const [mapModalOpen, setMapModalOpen] = useState(false);
  const [mapTargetIndex, setMapTargetIndex] = useState(null);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [showAccount, setShowAccount] = useState(false);

  const formatTimeForInput = (value) => {
    const s = String(value ?? "").trim();
    if (/^\d{2}:\d{2}:\d{2}$/.test(s)) return s.slice(0, 5);
    return s;
  };

  const appendSeconds = (value) => {
    const s = String(value ?? "").trim();
    if (/^\d{2}:\d{2}$/.test(s)) return `${s}:00`;
    return s;
  };

  const websiteOk = useMemo(() => {
    const website = (form.company_website || "").trim();
    if (!website) return true;
    try {
      const withProtocol =
        /^https?:\/\//i.test(website) ? website : `https://${website}`;
      new URL(withProtocol);
      return true;
    } catch (_) {
      return false;
    }
  }, [form.company_website]);

  const canSubmitBasic = useMemo(() => {
    const emailTrimmed = String(form.email || "").trim();
    const emailOk = emailTrimmed.length === 0 || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailTrimmed);
    const phone = String(form.phone_number || "").trim();
    const phoneOk = phone.length === 0 || /^\+?[0-9\s\-()]{7,32}$/.test(phone);
    return (
      (form.company_name || "").trim().length > 0 &&
      (form.legal_name || "").trim().length > 0 &&
      emailOk &&
      phoneOk &&
      String(form.company_business_id || "").trim().length > 0 &&
      websiteOk
    );
  }, [form, websiteOk]);

  const headBranchIndex = useMemo(() => branches.findIndex((b) => !!b.head_branch), [branches]);

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
  }, [headBranchIndex, contacts[headBranchIndex]?.country]);

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

  const loadTaxTemplatesForCountryEdit = useCallback(
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
    if (tab !== "tax") return;
    if (applyAllCountryTaxTemplates) return;
    loadTaxTemplatesForCountryEdit(taxCountryIso);
  }, [tab, taxCountryIso, applyAllCountryTaxTemplates, loadTaxTemplatesForCountryEdit]);

  const loadDropdowns = useCallback(async () => {
    if (!accessToken) return;
    try {
      const [payRes, featRes] = await Promise.all([
        companyService.getPaymentMethods(accessToken),
        companyService.getFeatures(accessToken),
      ]);
      setPaymentOptions(payRes.data || []);
      setFeatureOptions(featRes.data || []);
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    }
  }, [accessToken, addToastSafe]);

  useEffect(() => {
    loadCountries();
  }, [loadCountries]);

  useEffect(() => {
    if (tab === "payment" || tab === "features") loadDropdowns();
  }, [tab, loadDropdowns]);

  const applyCompanyData = useCallback((c) => {
    const rawStatus = c.company_status;
    const normalizedStatus =
      rawStatus === "A" || rawStatus === "Active" || rawStatus == 1 || rawStatus === "1" || rawStatus === true ? 1 : 0;
    const cust = c.customer_app === true || c.customer_app === 1 || c.customer_app === "1";
    const appt = c.appointment_auto_confirm === true || c.appointment_auto_confirm === 1 || c.appointment_auto_confirm === "1";

    setForm({
      company_name: c.company_name || "",
      legal_name: c.legal_name || "",
      tag_line: c.tag_line || "",
      description: c.description || "",
      phone_number: c.phone_number || "",
      email: c.email || "",
      company_business_id:
        c.company_business_id != null && String(c.company_business_id).trim() !== ""
          ? String(c.company_business_id)
          : "",
      company_website: c.company_website || "",
      company_dawn: formatTimeForInput(c.company_dawn),
      company_dusk: formatTimeForInput(c.company_dusk),
      company_status: normalizedStatus,
      customer_app: cust,
      appointment_auto_confirm: cust && appt,
      bank_name: c.bank_name || "",
      bank_code: c.bank_code || "",
      account_name: c.account_name || "",
      account_number: c.account_number || "",
    });

    setLogoKey(c.company_logo && !String(c.company_logo).startsWith("data:") ? c.company_logo : "");
    setLogoUrls(c.company_logo_urls || null);

    const brList = c.branches || [];
    if (brList.length === 0) {
      setBranches([{ ...defaultBranch }]);
      setContacts([{ ...defaultContact }]);
    } else {
      setBranches(brList.map((b) => mapBranchFromApi(b)));
      setContacts(brList.map((b) => mapContactFromApi(b.contact)));
    }

    const payRows = c.company_payments || c.companyPayments || [];
    setPaymentMethodIds(payRows.map((p) => p.payment_id ?? p.payment_method_id).filter(Boolean));
    const providers = {};
    payRows.forEach((row) => {
      const pid = row.payment_id ?? row.payment_method_id;
      if (!pid) return;
      providers[pid] = {
        merchant_id: row.merchant_id || "",
        secret_key: "",
      };
    });
    setPaymentProviders(providers);

    const featRows = c.company_features || c.companyFeatures || [];
    setFeatureIds(featRows.map((f) => f.feature_id).filter(Boolean));

    const taxMasters = c.tax_masters || c.taxMasters || [];
    if (taxMasters.length === 0) {
      setTaxes([]);
    } else {
      setTaxes(
        taxMasters.map((tm) => ({
          tax_name: tm.tax_name || "",
          components: (tm.components || []).length
            ? tm.components.map((comp) => ({
                component_name: comp.component_name || "",
                details: (comp.details || []).map((d) => ({
                  tax_value: d.tax_value ?? 0,
                  tax_start_date: d.tax_start_date ? String(d.tax_start_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
                  tax_end_date: d.tax_end_date ? String(d.tax_end_date).slice(0, 10) : "",
                })),
              }))
            : [{ component_name: "", details: [{ tax_value: 0, tax_start_date: new Date().toISOString().slice(0, 10), tax_end_date: "" }] }],
        }))
      );
    }

    const bh = c.business_hours || c.businessHours || [];
    setBusinessHours(
      bh.length
        ? bh.map((row) => ({
            day_of_week: row.day_of_week,
            is_open: !!row.is_open,
            opening_time: String(row.opening_time || "").slice(0, 5),
            closing_time: String(row.closing_time || "").slice(0, 5),
            slot_index: Number(row.slot_index || 1),
          }))
        : ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"].map((day) => ({
            day_of_week: day,
            is_open: true,
            opening_time: formatTimeForInput(c.company_dawn || "09:00"),
            closing_time: formatTimeForInput(c.company_dusk || "18:00"),
            slot_index: 1,
          }))
    );

    const settings = c.settings || {};
    setSettingsForm({
      enforce_2fa: !!settings.enforce_2fa,
      geo_fencing_enabled: !!settings.geo_fencing_enabled,
      geo_fencing_radius: settings.geo_fencing_radius != null ? String(settings.geo_fencing_radius) : "",
      appointment_time_slice_enabled: !!settings.appointment_time_slice_enabled,
      appointment_time_slice_minutes: settings.appointment_time_slice_minutes != null ? String(settings.appointment_time_slice_minutes) : "",
      auto_approve_appointments: !!settings.auto_approve_appointments,
      marketing_message: settings.marketing_message || "",
      public_company_page: !!settings.public_company_page,
    });
  }, []);

  useEffect(() => {
    if (!accessToken || !id) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const [lobRes, companyRes] = await Promise.all([
          (async () => {
            setLobLoading(true);
            try {
              const r = await lobService.getLineOfBusinessDropdowns(accessToken);
              return r.data || [];
            } finally {
              setLobLoading(false);
            }
          })(),
          companyService.getCompany(id, accessToken),
        ]);
        if (cancelled) return;
        setLobOptions(lobRes || []);
        applyCompanyData(companyRes.data || {});
      } catch (e) {
        if (!cancelled) addToastSafe("error", "Error", e.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [accessToken, id, addToastSafe, applyCompanyData]);

  const countryOptions = (countries || []).map((row) => ({
    value: row.country_name,
    label: row.country_name,
  }));

  const handleLogoFile = async (e) => {
    const f = e.target.files?.[0];
    e.target.value = "";
    if (!f || !accessToken || !id) return;
    if (f.size > 2 * 1024 * 1024) {
      addToastSafe("error", "File too large", "Logo must be 2MB or less.");
      return;
    }
    const ok = ["image/png", "image/jpeg", "image/svg+xml"].includes(f.type);
    if (!ok) {
      addToastSafe("error", "Invalid type", "Use PNG, JPG, or SVG.");
      return;
    }
    setLogoUploading(true);
    try {
      const res = await companyService.uploadCompanyLogo(id, f, accessToken);
      const data = res.data || {};
      setLogoKey(data.company_logo || "");
      setLogoUrls(data.company_logo_urls || null);
      addToastSafe("success", "Logo", "Logo uploaded.");
    } catch (err) {
      addToastSafe("error", "Upload failed", err.message);
    } finally {
      setLogoUploading(false);
    }
  };

  const handleRemoveLogo = async () => {
    if (!accessToken || !id) return;
    setLogoUploading(true);
    try {
      await companyService.deleteCompanyLogo(id, accessToken);
      setLogoKey("");
      setLogoUrls(null);
      addToastSafe("success", "Logo", "Logo removed.");
    } catch (e) {
      addToastSafe("error", "Error", e.message);
    } finally {
      setLogoUploading(false);
    }
  };

  const handleSaveBasic = async () => {
    if (!accessToken || !id) return;
    if (!canSubmitBasic) {
      addToastSafe("error", "Validation", "Please fill all mandatory basic info fields.");
      return;
    }
    setSubmitting(true);
    try {
      const custApp = !!form.customer_app;
      const payload = {
        company_name: form.company_name.trim(),
        legal_name: form.legal_name.trim(),
        tag_line: form.tag_line?.trim() || null,
        description: form.description?.trim() || null,
        phone_number: form.phone_number?.trim() || null,
        email: form.email?.trim() || null,
        company_business_id: form.company_business_id,
        company_website: form.company_website?.trim() || null,
        company_status: form.company_status,
        customer_app: custApp ? 1 : 0,
        appointment_auto_confirm: custApp && form.appointment_auto_confirm ? 1 : 0,
      };
      const res = await companyService.updateCompany(id, payload, accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Updated", "Company details saved.");
      setConfirmOpen(false);
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Update failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const addBranchAndContact = () => {
    setBranches((b) => [...b, { ...defaultBranch }]);
    setContacts((c) => [...c, { ...defaultContact }]);
  };

  const removeBranch = (i) => {
    if (branches.length <= 1) return;
    if (branches[i]?.head_branch) {
      addToastSafe("error", "Head branch required", "Mark another branch as Head before removing this one.");
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

  const handleSaveBranches = async () => {
    if (!accessToken || !id) return;
    if (headBranchIndex < 0) {
      addToastSafe("error", "Head branch", "Select one head branch.");
      return;
    }
    setSubmitting(true);
    try {
      const branchPayload = branches.map((b, idx) => ({
        branch_id: b.branch_id || undefined,
        branch_status: b.branch_status,
        branch_type: b.branch_type,
        head_branch: !!b.head_branch,
        branch_name: (b.branch_name || "").trim() || `Branch ${idx + 1}`,
        latitude: contacts[idx]?.latitude || b.latitude || null,
        longitude: contacts[idx]?.longitude || b.longitude || null,
      }));
      const contactPayload = contacts.map((c) => ({
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
      }));
      const res = await companyService.updateCompanyBranches(id, { branches: branchPayload, contacts: contactPayload }, accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Branches updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const togglePayment = (pid) =>
    setPaymentMethodIds((prev) => (prev.includes(pid) ? prev.filter((x) => x !== pid) : [...prev, pid]));
  const toggleFeature = (fid) =>
    setFeatureIds((prev) => (prev.includes(fid) ? prev.filter((x) => x !== fid) : [...prev, fid]));
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

  const handleSaveBank = async () => {
    if (!accessToken || !id) return;
    setSubmitting(true);
    try {
      const res = await companyService.updateCompany(
        id,
        {
          bank_name: form.bank_name?.trim() || null,
          bank_code: form.bank_code?.trim() || null,
          account_name: form.account_name?.trim() || null,
          account_number: form.account_number?.trim() || null,
        },
        accessToken
      );
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Bank information updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const updateBusinessHourRow = (idx, key, value) => {
    setBusinessHours((prev) => prev.map((row, i) => (i === idx ? { ...row, [key]: value } : row)));
  };

  const handleSaveBusinessHours = async () => {
    if (!accessToken || !id) return;
    const normalized = businessHours.map((row) => ({
      day_of_week: row.day_of_week,
      is_open: !!row.is_open,
      opening_time: row.is_open ? row.opening_time : null,
      closing_time: row.is_open ? row.closing_time : null,
      slot_index: Number(row.slot_index || 1),
    }));
    setSubmitting(true);
    try {
      const res = await companyService.updateCompanyBusinessHours(id, normalized, accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Business hours updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const handleSaveSettings = async () => {
    if (!accessToken || !id) return;
    const payload = {
      enforce_2fa: !!settingsForm.enforce_2fa,
      geo_fencing_enabled: !!settingsForm.geo_fencing_enabled,
      geo_fencing_radius: settingsForm.geo_fencing_enabled ? Number(settingsForm.geo_fencing_radius || 0) || null : null,
      appointment_time_slice_enabled: !!settingsForm.appointment_time_slice_enabled,
      appointment_time_slice_minutes: settingsForm.appointment_time_slice_enabled ? Number(settingsForm.appointment_time_slice_minutes || 0) || null : null,
      auto_approve_appointments: !!settingsForm.auto_approve_appointments,
      marketing_message: settingsForm.marketing_message || null,
      public_company_page: !!settingsForm.public_company_page,
    };
    setSubmitting(true);
    try {
      const res = await companyService.updateCompanySettings(id, payload, accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Company settings updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const handleSavePayment = async () => {
    if (!accessToken || !id) return;
    setSubmitting(true);
    try {
      const providersPayload = paymentMethodIds
        .map((pid) => ({
          payment_id: pid,
          merchant_id: paymentProviders[pid]?.merchant_id || null,
          secret_key: paymentProviders[pid]?.secret_key || null,
        }))
        .filter((row) => row.merchant_id || row.secret_key);
      const res = await companyService.updateCompanyPaymentMethods(id, paymentMethodIds, accessToken, providersPayload);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Payment methods updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const handleSaveFeatures = async () => {
    if (!accessToken || !id) return;
    setSubmitting(true);
    try {
      const res = await companyService.updateCompanyFeatures(id, featureIds, accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Features updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const addTax = () =>
    setTaxes((tx) => [
      ...tx,
      { tax_name: "", components: [{ component_name: "", details: [{ tax_value: 0, tax_start_date: new Date().toISOString().slice(0, 10), tax_end_date: "" }] }] },
    ]);
  const removeTax = (i) => setTaxes((tx) => tx.filter((_, j) => j !== i));
  const updateTax = (i, field, value) => setTaxes((tx) => tx.map((x, j) => (j === i ? { ...x, [field]: value } : x)));

  const handleSaveTax = async () => {
    if (!accessToken || !id) return;
    const nonEmpty = taxes.filter((tx) => String(tx?.tax_name || "").trim().length > 0);
    setSubmitting(true);
    try {
      const res = await companyService.updateCompanyTaxes(id, nonEmpty.length ? nonEmpty : [], accessToken);
      applyCompanyData(res.data || {});
      addToastSafe("success", "Saved", "Tax configuration updated.");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Save failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  const toggleTaxTemplateIdEdit = (tid) => {
    setSelectedTemplateIds((prev) => (prev.includes(tid) ? prev.filter((x) => x !== tid) : [...prev, tid]));
  };

  const taxCountrySelectOptionsEdit = [
    { value: "", label: countriesLoading ? "Loading…" : "Select country (ISO)…" },
    ...(countries || []).map((row) => ({
      value: String(row.country_code || "").toUpperCase(),
      label: `${row.country_name} (${row.country_code})`,
    })),
  ];

  const handleApplyTaxTemplates = async () => {
    if (!accessToken || !id) return;
    if (applyAllCountryTaxTemplates && (!taxCountryIso || String(taxCountryIso).trim().length !== 2)) {
      addToastSafe("error", "Tax country", "Select a country to apply all templates.");
      return;
    }
    setSubmitting(true);
    try {
      const res = await companyService.updateCompany(
        id,
        {
          country_code:
            taxCountryIso && String(taxCountryIso).trim().length === 2
              ? String(taxCountryIso).trim().toUpperCase()
              : undefined,
          selected_template_ids: applyAllCountryTaxTemplates ? [] : selectedTemplateIds,
          apply_all_country_tax_templates: applyAllCountryTaxTemplates,
        },
        accessToken
      );
      applyCompanyData(res.data || {});
      addToastSafe("success", "Applied", "Country tax templates applied (additive).");
      navigate(`/company/${id}`);
    } catch (e) {
      addToastSafe("error", "Apply failed", e.message);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div style={{ fontFamily: TYPE.fontBody }}>
        <PageHeader title="Edit Company" breadcrumb="Companies" />
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
            <TextSkeleton width={260} height={16} style={{ borderRadius: 10 }} />
            <TextSkeleton width={"100%"} height={12} />
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div style={{ fontFamily: TYPE.fontBody }}>
      <PageHeader
        title="Edit Company"
        subtitle="All sections"
        breadcrumb="Companies"
        action={
          <div style={{ display: "flex", gap: 8 }}>
            <Button variant="ghost" onClick={() => navigate(`/company/${id}`)}>
              Back to detail
            </Button>
          </div>
        }
      />

      <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 16 }}>
        {TABS.map((x) => (
          <button
            key={x.id}
            type="button"
            onClick={() => setTab(x.id)}
            style={{
              padding: "8px 14px",
              borderRadius: 6,
              border: `1px solid ${tab === x.id ? t.accent : t.border}`,
              background: tab === x.id ? t.accentSubtle : "transparent",
              color: tab === x.id ? t.accent : t.textSecondary,
              fontFamily: TYPE.fontBody,
              fontSize: TYPE.sm,
              cursor: "pointer",
            }}
          >
            {x.label}
          </button>
        ))}
      </div>

      {tab === "basic" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
            <Input
              label="Company name"
              value={form.company_name}
              onChange={(v) => setForm((f) => ({ ...f, company_name: v }))}
              placeholder="Acme Corp"
            />
            <Input
              label="Legal name"
              value={form.legal_name}
              onChange={(v) => setForm((f) => ({ ...f, legal_name: v }))}
              placeholder="Acme Corporation Pvt. Ltd."
            />
            <Input
              label="Tag line"
              value={form.tag_line}
              onChange={(v) => setForm((f) => ({ ...f, tag_line: v }))}
              placeholder="Built for scale"
            />

            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              {lobLoading ? (
                <TextSkeleton width={"100%"} height={38} style={{ borderRadius: 6 }} />
              ) : (
                <Select
                  label="Company line of business"
                  value={form.company_business_id}
                  onChange={(v) => setForm((f) => ({ ...f, company_business_id: v }))}
                  options={(lobOptions || []).map((o) => ({ value: o.lob_id, label: o.lob_name }))}
                />
              )}
              <Input
                label="Company website"
                value={form.company_website}
                onChange={(v) => setForm((f) => ({ ...f, company_website: v }))}
                placeholder="https://example.com"
              />
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              <Input
                label="Company email (optional)"
                value={form.email}
                onChange={(v) => setForm((f) => ({ ...f, email: v }))}
                placeholder="hello@company.com"
              />
              <Input
                label="Phone number"
                value={form.phone_number}
                onChange={(v) => setForm((f) => ({ ...f, phone_number: v }))}
                placeholder="+1 555 000 1111"
              />
            </div>
            <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase", fontFamily: TYPE.fontBody }}>
                Description
              </label>
              <textarea
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
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

            <div style={{ display: "flex", alignItems: "flex-start", gap: 16, flexWrap: "wrap" }}>
              <CompanyLogoAvatar name={form.company_name} logoUrls={logoUrls} size={72} />
              <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                <span style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                  Company logo
                </span>
                <input
                  type="file"
                  accept="image/png,image/jpeg,image/svg+xml"
                  onChange={handleLogoFile}
                  disabled={logoUploading}
                  style={{ fontSize: TYPE.sm, color: t.textSecondary }}
                />
                {logoKey ? (
                  <Button variant="ghost" size="sm" onClick={handleRemoveLogo} disabled={logoUploading}>
                    Remove logo
                  </Button>
                ) : null}
                <span style={{ fontSize: TYPE.xs, color: t.textMuted }}>
                  Stored on server (thumbnails generated). PNG, JPG, or SVG — max 2MB.
                </span>
              </div>
            </div>

            <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
              <label style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12, cursor: "pointer" }}>
                <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Enable Customer App</span>
                <input
                  type="checkbox"
                  checked={!!form.customer_app}
                  onChange={() =>
                    setForm((f) => {
                      const next = !f.customer_app;
                      return { ...f, customer_app: next, appointment_auto_confirm: next ? f.appointment_auto_confirm : false };
                    })
                  }
                  style={{ display: "none" }}
                />
                <span
                  style={{
                    width: 46,
                    height: 24,
                    borderRadius: 999,
                    background: form.customer_app ? t.accent : t.bgElevated,
                    border: `1px solid ${form.customer_app ? t.accent : t.border}`,
                    position: "relative",
                  }}
                >
                  <span
                    style={{
                      position: "absolute",
                      top: 3,
                      left: form.customer_app ? 23 : 3,
                      width: 18,
                      height: 18,
                      borderRadius: "50%",
                      background: form.customer_app ? "#000" : t.textMuted,
                    }}
                  />
                </span>
              </label>
              <label style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12, cursor: form.customer_app ? "pointer" : "not-allowed", opacity: form.customer_app ? 1 : 0.55 }}>
                <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Auto-confirm appointments</span>
                <input
                  type="checkbox"
                  checked={!!form.appointment_auto_confirm}
                  disabled={!form.customer_app}
                  onChange={() => {
                    if (!form.customer_app) return;
                    setForm((f) => ({ ...f, appointment_auto_confirm: !f.appointment_auto_confirm }));
                  }}
                  style={{ display: "none" }}
                />
                <span
                  style={{
                    width: 46,
                    height: 24,
                    borderRadius: 999,
                    background: form.customer_app && form.appointment_auto_confirm ? t.accent : t.bgElevated,
                    border: `1px solid ${form.customer_app && form.appointment_auto_confirm ? t.accent : t.border}`,
                    position: "relative",
                  }}
                >
                  <span
                    style={{
                      position: "absolute",
                      top: 3,
                      left: form.customer_app && form.appointment_auto_confirm ? 23 : 3,
                      width: 18,
                      height: 18,
                      borderRadius: "50%",
                      background: form.customer_app && form.appointment_auto_confirm ? "#000" : t.textMuted,
                    }}
                  />
                </span>
              </label>
            </div>

            <div>
              <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary, display: "block", marginBottom: 6 }}>Status</label>
              <select
                value={form.company_status}
                onChange={(e) => setForm((f) => ({ ...f, company_status: Number(e.target.value) }))}
                style={{
                  width: "100%",
                  height: 38,
                  padding: "0 12px",
                  background: t.bgElevated,
                  border: `1px solid ${t.border}`,
                  borderRadius: 6,
                  color: t.text,
                  fontFamily: TYPE.fontBody,
                }}
              >
                <option value={1}>Active</option>
                <option value={0}>Inactive</option>
              </select>
            </div>

            <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 12 }}>
              <Button variant="ghost" onClick={() => navigate(`/company/${id}`)} disabled={submitting}>
                Cancel
              </Button>
              <Button variant="primary" onClick={() => setConfirmOpen(true)} disabled={submitting || !canSubmitBasic}>
                {submitting ? "Saving…" : "Save section"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "branches" && (
        <Card>
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
                        <input type="radio" name="head-branch-edit" checked={!!b.head_branch} onChange={() => setHeadBranch(i)} style={{ accentColor: t.accent }} />
                        <span style={{ color: t.textSecondary, fontFamily: TYPE.fontBody, fontSize: TYPE.sm }}>Head</span>
                      </label>
                      <Button variant="ghost" size="sm" onClick={() => removeBranch(i)} disabled={branches.length <= 1 || !!b.head_branch}>
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
                        <option value="retail">Retail</option>
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
                    <Input label="Email" value={contacts[i].email} onChange={(v) => updateContact(i, "email", v)} disabled={!countryReady} />
                    <Input label="Address line" value={contacts[i].address1} onChange={(v) => updateContact(i, "address1", v)} style={{ gridColumn: "1 / -1" }} disabled={!countryReady} />
                    <Input label="Area" value={contacts[i].area} onChange={(v) => updateContact(i, "area", v)} disabled={!countryReady} />
                    <Input label="City" value={contacts[i].city} onChange={(v) => updateContact(i, "city", v)} disabled={!countryReady} />
                    <Input label="State / province" value={contacts[i].state} onChange={(v) => updateContact(i, "state", v)} disabled={!countryReady} />
                    <Input label="Postal code" value={contacts[i].pincode} onChange={(v) => updateContact(i, "pincode", v)} disabled={!countryReady} />
                    <Input label="Latitude" value={contacts[i].latitude} onChange={(v) => updateContact(i, "latitude", v)} disabled={!countryReady} />
                    <Input label="Longitude" value={contacts[i].longitude} onChange={(v) => updateContact(i, "longitude", v)} disabled={!countryReady} />
                    <div style={{ gridColumn: "1 / -1", display: "flex", justifyContent: "flex-end" }}>
                      <Button variant="dark" icon="🗺" disabled={!countryReady} onClick={() => { setMapTargetIndex(i); setMapModalOpen(true); }}>
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
              <div style={{ color: t.danger, fontSize: TYPE.sm }}>Select one branch as Head Branch.</div>
            )}
            <div style={{ display: "flex", justifyContent: "flex-end", gap: 10 }}>
              <Button variant="primary" onClick={handleSaveBranches} disabled={submitting}>
                {submitting ? "Saving…" : "Save branches"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "bank" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 14, maxWidth: 760 }}>
            <p style={{ fontSize: TYPE.sm, color: t.textMuted, margin: 0 }}>
              Keep bank details in a dedicated section for safer updates and easier review.
            </p>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              <Input label="Bank name" value={form.bank_name} onChange={(v) => setForm((f) => ({ ...f, bank_name: v }))} />
              <Input label="Bank code" value={form.bank_code} onChange={(v) => setForm((f) => ({ ...f, bank_code: v }))} />
              <Input label="Account name" value={form.account_name} onChange={(v) => setForm((f) => ({ ...f, account_name: v }))} />
              <div>
                <Input
                  label="Account number"
                  type={showAccount ? "text" : "password"}
                  value={form.account_number}
                  onChange={(v) => setForm((f) => ({ ...f, account_number: v }))}
                />
                <label style={{ display: "inline-flex", alignItems: "center", gap: 8, marginTop: 8, cursor: "pointer" }}>
                  <input type="checkbox" checked={showAccount} onChange={(e) => setShowAccount(e.target.checked)} />
                  <span style={{ fontSize: TYPE.sm, color: t.textSecondary }}>Show account number</span>
                </label>
                {!showAccount && form.account_number ? (
                  <div style={{ fontSize: TYPE.xs, color: t.textMuted, marginTop: 4 }}>Preview: {maskAccountNumber(form.account_number)}</div>
                ) : null}
              </div>
            </div>
            <div style={{ display: "flex", justifyContent: "flex-end" }}>
              <Button variant="primary" onClick={handleSaveBank} disabled={submitting}>
                {submitting ? "Saving…" : "Save bank info"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "hours" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
            {businessHours.map((row, idx) => (
              <div key={`${row.day_of_week}-${idx}`} style={{ padding: 14, border: `1px solid ${t.border}`, borderRadius: 8, display: "grid", gridTemplateColumns: "160px 120px 1fr 1fr", gap: 12, alignItems: "center" }}>
                <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{row.day_of_week}</div>
                <div style={{ display: "inline-flex", alignItems: "center", gap: 8, color: t.textSecondary, fontSize: TYPE.sm }}>
                  <ToggleSwitch
                    checked={!!row.is_open}
                    onChange={(next) => updateBusinessHourRow(idx, "is_open", next)}
                    ariaLabel={`${row.day_of_week} open status`}
                  />
                  Open
                </div>
                <Input
                  label="Opening"
                  type="time"
                  value={row.opening_time || ""}
                  onChange={(v) => updateBusinessHourRow(idx, "opening_time", v)}
                  disabled={!row.is_open}
                />
                <Input
                  label="Closing"
                  type="time"
                  value={row.closing_time || ""}
                  onChange={(v) => updateBusinessHourRow(idx, "closing_time", v)}
                  disabled={!row.is_open}
                />
              </div>
            ))}
            <div style={{ display: "flex", justifyContent: "flex-end" }}>
              <Button variant="primary" onClick={handleSaveBusinessHours} disabled={submitting}>
                {submitting ? "Saving…" : "Save business hours"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "settings" && (
        <Card>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
            {[
              ["enforce_2fa", "Enforce 2FA"],
              ["geo_fencing_enabled", "Geo fencing enabled"],
              ["appointment_time_slice_enabled", "Appointment time slice enabled"],
              ["auto_approve_appointments", "Auto approve appointments"],
              ["public_company_page", "Public company page"],
            ].map(([key, label]) => (
              <label key={key} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", border: `1px solid ${t.border}`, borderRadius: 8, padding: "10px 12px", color: t.textSecondary }}>
                <span>{label}</span>
                <ToggleSwitch
                  checked={!!settingsForm[key]}
                  onChange={(next) => setSettingsForm((prev) => ({ ...prev, [key]: next }))}
                  ariaLabel={label}
                />
              </label>
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
            <div style={{ gridColumn: "1 / -1", display: "flex", justifyContent: "flex-end" }}>
              <Button variant="primary" onClick={handleSaveSettings} disabled={submitting}>
                {submitting ? "Saving…" : "Save settings"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "payment" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            {!paymentOptions.length ? (
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
                            <div style={{ color: t.text, fontWeight: TYPE.semibold, fontFamily: TYPE.fontBody }}>{pm.payment_name}</div>
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
                <div style={{ display: "flex", justifyContent: "flex-end" }}>
                  <Button variant="primary" onClick={handleSavePayment} disabled={submitting}>
                    {submitting ? "Saving…" : "Save payment methods"}
                  </Button>
                </div>
              </>
            )}
          </div>
        </Card>
      )}

      {tab === "tax" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
            <div style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 8, background: t.bgElevated }}>
              <div style={{ fontWeight: TYPE.semibold, color: t.text, marginBottom: 8 }}>Apply country tax templates</div>
              <p style={{ fontSize: TYPE.sm, color: t.textMuted, margin: "0 0 12px 0" }}>
                Clones admin templates into this company (skips duplicates when traceability is enabled). Does not replace manual rows below.
              </p>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
                <Select label="Country (ISO)" value={taxCountryIso} onChange={setTaxCountryIso} options={taxCountrySelectOptionsEdit} />
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
                            onChange={() => toggleTaxTemplateIdEdit(tm.template_tax_id || tm.tax_id)}
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
              <div style={{ display: "flex", justifyContent: "flex-end", marginTop: 12 }}>
                <Button variant="primary" onClick={handleApplyTaxTemplates} disabled={submitting}>
                  {submitting ? "Applying…" : "Apply templates"}
                </Button>
              </div>
            </div>

            <p style={{ fontSize: TYPE.sm, color: t.textMuted, margin: 0 }}>Manual tax editor replaces all tax rows for this company. Leave empty to clear taxes.</p>
            {taxes.map((tax, i) => (
              <div key={i} style={{ padding: 16, border: `1px solid ${t.border}`, borderRadius: 8 }}>
                <div style={{ display: "flex", gap: 12, alignItems: "center", marginBottom: 8 }}>
                  <Input label="Tax name" value={tax.tax_name} onChange={(v) => updateTax(i, "tax_name", v)} placeholder="e.g. GST" style={{ flex: 1 }} />
                  <Button variant="ghost" size="sm" onClick={() => removeTax(i)}>
                    Remove
                  </Button>
                </div>
              </div>
            ))}
            <Button variant="dark" icon="+" onClick={addTax}>
              Add tax
            </Button>
            <div style={{ display: "flex", justifyContent: "flex-end" }}>
              <Button variant="primary" onClick={handleSaveTax} disabled={submitting}>
                {submitting ? "Saving…" : "Save taxes"}
              </Button>
            </div>
          </div>
        </Card>
      )}

      {tab === "features" && (
        <Card>
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            {!featureOptions.length ? (
              <Loader />
            ) : (
              <>
                <label style={{ fontSize: TYPE.xs, fontWeight: TYPE.semibold, color: t.textSecondary }}>Features</label>
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
                        <div style={{ color: t.text, fontWeight: TYPE.semibold }}>{f.feature_name}</div>
                        <div style={{ display: "flex", justifyContent: "flex-end" }}>
                          <ToggleSwitch
                            checked={checked}
                            onChange={() => toggleFeature(f.feature_id)}
                            ariaLabel={`${f.feature_name} feature toggle`}
                          />
                        </div>
                      </div>
                    );
                  })}
                </div>
                <div style={{ display: "flex", justifyContent: "flex-end" }}>
                  <Button variant="primary" onClick={handleSaveFeatures} disabled={submitting}>
                    {submitting ? "Saving…" : "Save features"}
                  </Button>
                </div>
              </>
            )}
          </div>
        </Card>
      )}

      <Modal open={confirmOpen} onClose={() => !submitting && setConfirmOpen(false)} title="Confirm update" width={500}>
        <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <div style={{ color: t.textSecondary, fontSize: TYPE.sm }}>
            Update basic info for <strong style={{ color: t.text }}>{form.company_name || "company"}</strong>?
          </div>
          <div style={{ display: "flex", justifyContent: "flex-end", gap: 10 }}>
            <Button variant="ghost" onClick={() => !submitting && setConfirmOpen(false)} disabled={submitting}>
              Cancel
            </Button>
            <Button variant="primary" onClick={handleSaveBasic} disabled={submitting}>
              {submitting ? "Saving…" : "Confirm"}
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
