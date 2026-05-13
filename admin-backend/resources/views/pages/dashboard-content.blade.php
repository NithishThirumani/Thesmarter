
    <style>
        .dashboard-container {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
			min-height:100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        .card-body {
            padding: 1.25rem;
        }

        .announcement-category {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .category-critical {
            background-color: #fff2f2;
            color: #dc3545;
        }

        .category-organization {
            background-color: #e8f4fd;
            color: #0d6efd;
        }

        .category-individual {
            background-color: #e8f8e8;
            color: #198754;
        }

        .list-group-item {
            border: none;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .order-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-shipped {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }

        .attendance-status {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            background-color: #e8f8e8;
            color: #198754;
            font-weight: 500;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <!-- ToDos Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-tasks me-2"></i>ToDos</span>
                        <button class="btn btn-sm btn-outline-primary">Add Task</button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <input type="checkbox" class="form-check-input me-2">
                                    Complete monthly report
                                </div>
                                <span class="badge bg-warning text-dark">Due Today</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <input type="checkbox" class="form-check-input me-2">
                                    Team meeting at 2 PM
                                </div>
                                <span class="badge bg-info">Upcoming</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <input type="checkbox" class="form-check-input me-2">
                                    Follow up with client
                                </div>
                                <span class="badge bg-danger">Priority</span>
                            </li>
                            <li class="list-group-item text-center text-muted">
                                <small>+3 more tasks</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Announcements Card -->
             <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-bullhorn me-2"></i>Announcements</span>
                        <button class="btn btn-sm btn-outline-secondary">View All</button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="announcement-category category-critical">
                                <i class="fas fa-exclamation-circle me-1"></i>Critical
                            </span>
                            <p class="mb-0">Server maintenance scheduled for tonight at 10 PM.</p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <span class="announcement-category category-organization">
                                <i class="fas fa-building me-1"></i>Organization
                            </span>
                            <p class="mb-0">Company holiday announced for next Friday.</p>
                        </div>
                        <hr>
                        <div>
                            <span class="announcement-category category-individual">
                                <i class="fas fa-user me-1"></i>Individual
                            </span>
                            <p class="mb-0">Performance review meeting on Wednesday.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Attendance Card -->
            <div class="col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock me-2"></i>Attendance</span>
                        <div class="attendance-status">
                            <i class="fas fa-check-circle me-1"></i>Present
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Punch In</th>
                                        <th>Punch Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2023-12-19</td>
                                        <td><i class="fas fa-sign-in-alt text-success me-1"></i>9:00 AM</td>
                                        <td><i class="fas fa-sign-out-alt text-danger me-1"></i>5:30 PM</td>
                                        <td><span class="badge bg-success">On Time</span></td>
                                    </tr>
                                    <tr>
                                        <td>2023-12-18</td>
                                        <td><i class="fas fa-sign-in-alt text-success me-1"></i>9:15 AM</td>
                                        <td><i class="fas fa-sign-out-alt text-danger me-1"></i>6:00 PM</td>
                                        <td><span class="badge bg-warning text-dark">Late</span></td>
                                    </tr>
                                    <tr>
                                        <td>2023-12-17</td>
                                        <td><i class="fas fa-sign-in-alt text-success me-1"></i>9:05 AM</td>
                                        <td><i class="fas fa-sign-out-alt text-danger me-1"></i>5:45 PM</td>
                                        <td><span class="badge bg-success">On Time</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Orders Card -->
            <div class="col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-shopping-cart me-2"></i>Latest Orders</span>
                        <button class="btn btn-sm btn-outline-secondary">View All</button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-box me-2"></i>Order #12345</span>
                                <span class="order-status status-pending">Pending</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-box me-2"></i>Order #12344</span>
                                <span class="order-status status-shipped">Shipped</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-box me-2"></i>Order #12343</span>
                                <span class="order-status status-delivered">Delivered</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-box me-2"></i>Order #12342</span>
                                <span class="order-status status-cancelled">Cancelled</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
       
    </script>
</body>
</html>