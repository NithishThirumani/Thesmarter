import React, { Component } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider, useAuth } from "./auth/authContext";
import ProtectedRoute from "./auth/ProtectedRoute";
import AuthScreen from "./auth/pages/AuthScreen";
import DashboardLayout from "./layout/DashboardLayout";
import DashboardPage from "./pages/dashboard/DashboardPage";
import TenantPage from "./pages/tenants/TenantPage";
import CompanyListPage from "./pages/company/CompanyListPage";
import CompanyWizardPage from "./pages/company/CompanyWizardPage";
import CompanyDetailPage from "./pages/company/CompanyDetailPage";
import CompanyEditPage from "./pages/company/CompanyEditPage";
import CreateSuperUserPage from "./pages/company/CreateSuperUserPage";
import EditSuperUserPage from "./pages/company/EditSuperUserPage";
import LineOfBusinessListPage from "./pages/line-of-business/LineOfBusinessListPage";
import LineOfBusinessCreatePage from "./pages/line-of-business/LineOfBusinessCreatePage";
import LineOfBusinessEditPage from "./pages/line-of-business/LineOfBusinessEditPage";
import LineOfBusinessDetailPage from "./pages/line-of-business/LineOfBusinessDetailPage";
import FeaturePage from "./pages/features/FeaturePage";
import SuperUserPage from "./pages/users/SuperUserPage";
import SuperAdminRoute from "./auth/SuperAdminRoute";
import PlatformAdminPage from "./pages/platform-admin/PlatformAdminPage";
import PaymentPage from "./pages/payments/PaymentPage";
import TaxPage from "./pages/tax/TaxPage";
import CataloguePage from "./pages/catalogue/CataloguePage";
import EmailPage from "./pages/email/EmailPage";
import AgentPage from "./pages/agents/AgentPage";
import ThirdPartyPage from "./pages/integrations/ThirdPartyPage";
import AuditPage from "./pages/audit/AuditPage";
import NotificationsPage from "./pages/notifications/NotificationsPage";
import RolesPage from "./pages/roles/RolesPage";
import SettingsPage from "./pages/settings/SettingsPage";
import { ROUTES } from "./routes";
import { useTheme } from "./theme";

class AppErrorBoundary extends Component {
  state = { hasError: false, error: null };
  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }
  render() {
    if (this.state.hasError) {
      return (
        <div
          style={{
            minHeight: "100vh",
            padding: 24,
            background: "#0a0a0a",
            color: "#ef4444",
            fontFamily: "system-ui, sans-serif",
          }}
        >
          <h2>Something went wrong</h2>
          <pre style={{ fontSize: 12, overflow: "auto" }}>{this.state.error?.message || this.state.error?.toString()}</pre>
        </div>
      );
    }
    return this.props.children;
  }
}

/** Public route: redirect to /dashboard if already logged in */
function PublicRoute({ children }) {
  const { user, loading, initialCheckDone } = useAuth();
  const { tokens: t } = useTheme();
  if (!initialCheckDone || loading) {
    return (
      <div
        style={{
          minHeight: "100vh",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: t.bg,
          color: t.textMuted,
        }}
      >
        Loading…
      </div>
    );
  }
  if (user) {
    return <Navigate to={ROUTES.DASHBOARD} replace />;
  }
  return children;
}

export default function App() {
  return (
    <AppErrorBoundary>
      <BrowserRouter>
        <AuthProvider>
          <Routes>
            {/* Unprotected: login */}
            <Route
              path="/login"
              element={
                <PublicRoute>
                  <AuthScreen />
                </PublicRoute>
              }
            />

            {/* Protected: all dashboard routes with Sidebar + Topbar */}
            <Route
              path="/"
              element={
                <ProtectedRoute>
                  <DashboardLayout />
                </ProtectedRoute>
              }
            >
              <Route index element={<Navigate to={ROUTES.DASHBOARD} replace />} />
              <Route path="dashboard" element={<DashboardPage />} />
              <Route path="tenants" element={<TenantPage />} />
              <Route path="company" element={<CompanyListPage />} />
              <Route path="company/new" element={<CompanyWizardPage />} />
              <Route path="company/:id" element={<CompanyDetailPage />} />
              <Route path="company/:id/edit" element={<CompanyEditPage />} />
              <Route path="admin/create-super-user" element={<CreateSuperUserPage />} />
              <Route path="admin/companies/:companyId/create-super-user" element={<CreateSuperUserPage />} />
              <Route path="admin/companies/:companyId/edit-super-user/:userId" element={<EditSuperUserPage />} />
              <Route path="line-of-business" element={<LineOfBusinessListPage />} />
              <Route path="line-of-business/new" element={<LineOfBusinessCreatePage />} />
              <Route path="line-of-business/:id/edit" element={<LineOfBusinessEditPage />} />
              <Route path="line-of-business/:id" element={<LineOfBusinessDetailPage />} />
              <Route path="features" element={<FeaturePage />} />
              <Route path="superusers" element={<SuperUserPage />} />
              <Route
                path="platform-admin"
                element={
                  <SuperAdminRoute>
                    <PlatformAdminPage />
                  </SuperAdminRoute>
                }
              />
              <Route path="payments" element={<PaymentPage />} />
              <Route path="tax" element={<TaxPage />} />
              <Route path="catalogue" element={<CataloguePage />} />
              <Route path="email" element={<EmailPage />} />
              <Route path="agents" element={<AgentPage />} />
              <Route path="integrations" element={<ThirdPartyPage />} />
              <Route path="audit" element={<AuditPage />} />
              <Route path="notifications" element={<NotificationsPage />} />
              <Route path="roles" element={<RolesPage />} />
              <Route path="settings" element={<SettingsPage />} />
              <Route path="*" element={<Navigate to={ROUTES.DASHBOARD} replace />} />
            </Route>

            {/* Fallback */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </AppErrorBoundary>
  );
}
