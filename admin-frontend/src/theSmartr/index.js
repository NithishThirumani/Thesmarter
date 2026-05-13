/**
 * theSmartr shared data and nav — fixes missing module imports used by Sidebar, Dashboard, Tenant, Audit.
 */

export const CHART_DATA = [
  { month: "Jan", revenue: 38, tenants: 28, sessions: 8200 },
  { month: "Feb", revenue: 40, tenants: 29, sessions: 8500 },
  { month: "Mar", revenue: 42, tenants: 30, sessions: 8800 },
  { month: "Apr", revenue: 41, tenants: 31, sessions: 9100 },
  { month: "May", revenue: 43, tenants: 32, sessions: 9500 },
  { month: "Jun", revenue: 45, tenants: 34, sessions: 9812 },
];

export const MOCK_AUDIT = [
  { id: 1, action: "Tenant created", details: "Acme Corp (ACME001) — Pro plan", time: "2025-06-17 09:32" },
  { id: 2, action: "Subscription renewed", details: "GlobalTrade — Enterprise", time: "2025-06-17 10:15" },
  { id: 3, action: "User invited", details: "admin@novasystems.io — Admin role", time: "2025-06-17 11:02" },
  { id: 4, action: "Plan upgraded", details: "PrimeSoft — Trial → Pro", time: "2025-06-17 14:20" },
];

export const MOCK_TENANTS = [
  { id: 1, name: "Acme Corp", code: "ACME001", domain: "acme.thesmartr.com", email: "admin@acme.com", plan: "Pro", status: "Active", users: 12, expiry: "2025-12-31", revenue: 0, created: "2025-01-15" },
  { id: 2, name: "GlobalTrade", code: "GLB001", domain: "global.thesmartr.com", email: "billing@globaltrade.com", plan: "Enterprise", status: "Active", users: 45, expiry: "2025-11-30", revenue: 0, created: "2025-02-01" },
  { id: 3, name: "PrimeSoft", code: "PRM001", domain: "prime.thesmartr.com", email: "admin@primesoft.io", plan: "Starter", status: "Trial", users: 3, expiry: "2025-07-01", revenue: 0, created: "2025-06-01" },
];

export const NAV_ITEMS = [
  { key: "dashboard", path: "/dashboard", label: "Dashboard", icon: "▣", group: null },
  // { key: "tenants", path: "/tenants", label: "Tenants", icon: "⬡", group: "Management" },
  { key: "company", path: "/company", label: "Companies", icon: "🏢", group: "Management" },
  { key: "lineofbusiness", path: "/line-of-business", label: "Line of Business", icon: "🧩", group: "Management" },
  { key: "features", path: "/features", label: "Features", icon: "⚙", group: "Management" },
  { key: "superusers", path: "/superusers", label: "Super Users", icon: "👤", group: "Management" },
  { key: "platformadmins", path: "/platform-admin", label: "Super admins", icon: "👑", group: "Management", requireSuperAdmin: true },
  { key: "payments", path: "/payments", label: "Payments", icon: "💰", group: "Billing" },
  { key: "tax", path: "/tax", label: "Tax", icon: "📋", group: "Billing" },
  { key: "catalogue", path: "/catalogue", label: "Catalogue", icon: "📦", group: "Content" },
  { key: "email", path: "/email", label: "Email", icon: "✉", group: "Content" },
  // { key: "agents", path: "/agents", label: "Agents", icon: "🤖", group: "Integrations" },
  // { key: "thirdparty", path: "/integrations", label: "Integrations", icon: "🔌", group: "Integrations" },
  { key: "audit", path: "/audit", label: "Audit", icon: "📜", group: "Security" },
  { key: "notifications", path: "/notifications", label: "Notifications", icon: "🔔", group: "Security" },
  // { key: "roles", path: "/roles", label: "Roles", icon: "🔐", group: "Security" },
  // { key: "settings", path: "/settings", label: "Settings", icon: "⚙", group: null },
];
