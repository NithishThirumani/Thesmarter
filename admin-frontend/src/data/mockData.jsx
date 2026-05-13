
export const MOCK_TENANTS = [
    { id: 1, name: "Acme Corp", code: "ACME001", domain: "acme.thesmartr.com", status: "Active", plan: "Enterprise", email: "admin@acme.com", created: "2024-01-15", expiry: "2025-01-15", users: 124, revenue: 4800 },
    { id: 2, name: "TechVentures", code: "TECH002", domain: "techv.thesmartr.com", status: "Active", plan: "Pro", email: "ops@techv.io", created: "2024-02-10", expiry: "2025-02-10", users: 67, revenue: 2400 },
    { id: 3, name: "GlobalTrade", code: "GLOB003", domain: "global.thesmartr.com", status: "Suspended", plan: "Starter", email: "it@globaltrade.net", created: "2023-12-01", expiry: "2024-12-01", users: 23, revenue: 800 },
    { id: 4, name: "NovaSystems", code: "NOVA004", domain: "nova.thesmartr.com", status: "Active", plan: "Enterprise", email: "admin@novasys.com", created: "2024-03-20", expiry: "2025-03-20", users: 210, revenue: 9600 },
    { id: 5, name: "PrimeSoft", code: "PRIM005", domain: "prime.thesmartr.com", status: "Trial", plan: "Starter", email: "cto@primesoft.co", created: "2024-06-01", expiry: "2024-07-01", users: 8, revenue: 0 },
  ];
  
  export const MOCK_USERS = [
    { id: 1, name: "Ethan Mercer", email: "ethan@thesmartr.com", role: "Super Admin", status: "Active", lastLogin: "2024-06-15 14:32", permissions: ["all"] },
    { id: 2, name: "Priya Nair", email: "priya@thesmartr.com", role: "Admin", status: "Active", lastLogin: "2024-06-14 09:11", permissions: ["tenants", "features"] },
    { id: 3, name: "Liam Zhao", email: "liam@thesmartr.com", role: "Support", status: "Active", lastLogin: "2024-06-13 17:05", permissions: ["tenants"] },
    { id: 4, name: "Sofia Reyes", email: "sofia@thesmartr.com", role: "Finance", status: "Inactive", lastLogin: "2024-05-20 11:45", permissions: ["payments", "tax"] },
  ];
  
  export const MOCK_FEATURES = [
    { id: 1, name: "Advanced Analytics", code: "ANALYTICS", module: "Reports", status: "Active", tenants: 3 },
    { id: 2, name: "Multi-Currency", code: "MULTICURR", module: "Finance", status: "Active", tenants: 4 },
    { id: 3, name: "API Gateway", code: "APIGATE", module: "Integration", status: "Active", tenants: 2 },
    { id: 4, name: "AI Forecasting", code: "AIFCAST", module: "Intelligence", status: "Beta", tenants: 1 },
    { id: 5, name: "Bulk Import", code: "BULKIMP", module: "Data", status: "Active", tenants: 5 },
  ];
  
  export const MOCK_AUDIT = [
    { id: 1, user: "Ethan Mercer", action: "Tenant Created", module: "Tenants", details: "Created tenant 'PrimeSoft'", ip: "192.168.1.1", time: "2024-06-15 14:32:01" },
    { id: 2, user: "Priya Nair", action: "Feature Enabled", module: "Features", details: "Enabled AI Forecasting for NovaSystems", ip: "10.0.0.23", time: "2024-06-14 11:20:15" },
    { id: 3, user: "Liam Zhao", action: "Tenant Suspended", module: "Tenants", details: "Suspended GlobalTrade due to payment failure", ip: "172.16.0.5", time: "2024-06-13 09:05:30" },
    { id: 4, user: "System", action: "Token Refreshed", module: "Auth", details: "OAuth2 token auto-refreshed for session #4891", ip: "–", time: "2024-06-15 14:00:00" },
    { id: 5, user: "Sofia Reyes", action: "Tax Rule Updated", module: "Tax", details: "Updated GST rule for IN-GST-18", ip: "10.0.1.88", time: "2024-06-12 16:44:22" },
  ];
  
  export const MOCK_PAYMENTS = [
    { id: 1, method: "Credit Card", provider: "Stripe", status: "Active", countries: "US, EU, GB, IN" },
    { id: 2, method: "UPI", provider: "Razorpay", status: "Active", countries: "IN" },
    { id: 3, method: "PayPal", provider: "PayPal", status: "Active", countries: "US, EU, CA, AU" },
    { id: 4, method: "Net Banking", provider: "Razorpay", status: "Inactive", countries: "IN" },
  ];
  
  export const CHART_DATA = [
    { month: "Jan", tenants: 12, revenue: 18400, sessions: 3200 },
    { month: "Feb", tenants: 15, revenue: 21000, sessions: 4100 },
    { month: "Mar", tenants: 18, revenue: 24800, sessions: 5500 },
    { month: "Apr", tenants: 22, revenue: 29200, sessions: 6800 },
    { month: "May", tenants: 28, revenue: 35600, sessions: 8200 },
    { month: "Jun", tenants: 34, revenue: 43000, sessions: 9800 },
  ];
  