<style>
    .security-card {
        margin-bottom: 25px;
        border: none;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    .security-icon {
        font-size: 1.5rem;
        width: 40px;
        color: #0d6efd;
    }

    .status-badge {
        font-size: 0.8rem;
        padding: 5px 10px;
    }

    .device-icon {
        font-size: 1.2rem;
        color: #6c757d;
    }

    .last-activity {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .pin-input-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 20px 0;
    }

    .pin-input-box {
        width: 50px;
        height: 50px;
        text-align: center;
        font-size: 1.5rem;
        border: 2px solid #ced4da;
        border-radius: 8px;
    }

    .security-heading {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .activity-timeline {
        position: relative;
        padding-left: 30px;
    }

    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }

    .activity-item {
        position: relative;
        margin-bottom: 20px;
    }

    .activity-item::before {
        content: '';
        position: absolute;
        left: -30px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #0d6efd;
    }

</style>

<div class="container-fluid">
    <h2 class="security-heading">Security Settings</h2>

    <!-- Two-Factor Authentication -->
    {{-- <div class="card security-card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-shield-alt security-icon me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Two-Factor Authentication</h5>
                    <p class="mb-0 text-muted">Add an extra layer of security to your account</p>
                </div>
                <span class="badge bg-success status-badge">Enabled</span>
            </div>
            <div class="mt-3">
                <button class="btn btn-outline-primary btn-sm">Manage 2FA</button>
            </div>
        </div>
    </div> --}}

    <!-- Password Settings -->
    {{-- <div class="card security-card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-key security-icon me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Password Settings</h5>
                    <p class="mb-0 text-muted">Last changed: 2 months ago</p>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-outline-primary btn-sm me-2">Change Password</button>
                <button class="btn btn-outline-secondary btn-sm">Set up Recovery</button>
            </div>
        </div>
    </div> --}}

    <!-- Active Sessions -->
    {{-- <div class="card security-card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-desktop security-icon me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Active Sessions</h5>
                    <p class="mb-0 text-muted">Manage your active sessions across devices</p>
                </div>
            </div>
            <div class="list-group">
                <div class="list-group-item d-flex align-items-center">
                    <i class="fas fa-laptop device-icon me-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">MacBook Pro - Chrome</h6>
                        <span class="last-activity">Current session</span>
                    </div>
                    <span class="badge bg-primary status-badge">Active Now</span>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="fas fa-mobile-alt device-icon me-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">iPhone 13 - Safari</h6>
                        <span class="last-activity">Last active: 2 hours ago</span>
                    </div>
                    <button class="btn btn-outline-danger btn-sm">End Session</button>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Security Activity -->
    {{-- <div class="card security-card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-history security-icon me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Security Activity</h5>
                    <p class="mb-0 text-muted">Recent security events on your account</p>
                </div>
            </div>
            <div class="activity-timeline">
                <div class="activity-item">
                    <h6 class="mb-1">New device signed in</h6>
                    <p class="mb-0 text-muted">iPhone 13 - New York, USA</p>
                    <small class="text-muted">2 hours ago</small>
                </div>
                <div class="activity-item">
                    <h6 class="mb-1">Password changed</h6>
                    <p class="mb-0 text-muted">Through account settings</p>
                    <small class="text-muted">2 months ago</small>
                </div>
                <div class="activity-item">
                    <h6 class="mb-1">Two-factor authentication enabled</h6>
                    <p class="mb-0 text-muted">Using Google Authenticator</p>
                    <small class="text-muted">3 months ago</small>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Account Deletion -->
    <div class="card security-card border-danger">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-exclamation-triangle text-danger security-icon me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Delete Account</h5>
                    <p class="mb-0 text-muted">Permanently remove your account and all associated data</p>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-danger" id="deleteAccountBtn">Delete Account Permanently</button>
            </div>
        </div>
    </div>
</div>

<!-- PIN Verification Modal -->
<div class="modal fade" id="pinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please enter your 4-digit PIN to confirm account deletion</p>
                <div class="pin-input-group">
                    <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                </div>
                <div class="invalid-feedback d-none">
                    Invalid PIN. Please try again.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Confirm Deletion</button>
            </div>
        </div>
    </div>
</div>

