<div class="orders-container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-3"><i class="fas fa-shopping-bag me-2"></i>Orders</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="btn-group me-2">
                <select class="form-select form-select-sm branch-select">
                    <option value="all" selected>All Branches</option>
                    <option value="branch1">Branch 1</option>
                    <option value="branch2">Branch 2</option>
                    <option value="branch3">Branch 3</option>
                </select>
            </div>
            <div class="btn-group me-2">
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-file-export me-1"></i>Export
                </button>
            </div>
            <div class="btn-group me-2">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="moreActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v me-1"></i>More Actions
                </button>
                <ul class="dropdown-menu" aria-labelledby="moreActionsDropdown">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-print me-2"></i>Print Orders</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-tags me-2"></i>Edit Tags</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-archive me-2"></i>Archive Selected</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-trash me-2"></i>Delete Selected</a></li>
                </ul>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Create Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-4 border-end">
                            <h6 class="text-muted">Total Orders</h6>
                            <h3>237</h3>
                        </div>
                        <div class="col-md-4 border-end">
                            <h6 class="text-muted">Total Sales</h6>
                            <h3>₹24,350</h3>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Returns</h6>
                            <h3>12</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Date range:</span>
                                <input type="text" class="form-control form-control-sm" placeholder="Select date" value="Mar 19, 2025">
                                <i class="fas fa-calendar-alt ms-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Categories -->
    <div class="row mb-4">
        <div class="col-md-9">
            <ul class="nav nav-tabs order-categories">
                <li class="nav-item">
                    <a class="nav-link active" href="#">All <span class="badge bg-secondary">237</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Unpaid <span class="badge bg-warning text-dark">43</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Unfulfilled <span class="badge bg-info">85</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Open <span class="badge bg-success">192</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Archived <span class="badge bg-secondary">45</span></a>
                </li>
            </ul>
        </div>
        <div class="col-md-3 text-end">
            <button class="btn btn-sm btn-outline-secondary" id="filterButton">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th>Order No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Channel</th>
                            <th>Total</th>
                            <th>Payment Status</th>
                            <th>Fulfillment Status</th>
                            <th>Items</th>
                            <th>Delivery Status</th>
                            <th>Delivery Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td><a href="#">ORD-12345</a></td>
                            <td>Mar 19, 2025</td>
                            <td>John Doe</td>
                            <td><span class="badge bg-primary">Web</span></td>
                            <td>₹1,250</td>
                            <td><span class="order-status status-pending">Pending</span></td>
                            <td><span class="order-status status-pending">Unfulfilled</span></td>
                            <td>3</td>
                            <td><span class="order-status status-pending">Pending</span></td>
                            <td>Standard</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td><a href="#">ORD-12344</a></td>
                            <td>Mar 18, 2025</td>
                            <td>Jane Smith</td>
                            <td><span class="badge bg-success">Mobile</span></td>
                            <td>₹2,150</td>
                            <td><span class="order-status status-delivered">Paid</span></td>
                            <td><span class="order-status status-shipped">Partial</span></td>
                            <td>2</td>
                            <td><span class="order-status status-shipped">Shipped</span></td>
                            <td>Express</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td><a href="#">ORD-12343</a></td>
                            <td>Mar 17, 2025</td>
                            <td>Mike Johnson</td>
                            <td><span class="badge bg-info">In-store</span></td>
                            <td>₹750</td>
                            <td><span class="order-status status-delivered">Paid</span></td>
                            <td><span class="order-status status-delivered">Fulfilled</span></td>
                            <td>1</td>
                            <td><span class="order-status status-delivered">Delivered</span></td>
                            <td>Standard</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td><a href="#">ORD-12342</a></td>
                            <td>Mar 16, 2025</td>
                            <td>Sarah Wilson</td>
                            <td><span class="badge bg-primary">Web</span></td>
                            <td>₹3,450</td>
                            <td><span class="order-status status-pending">Pending</span></td>
                            <td><span class="order-status status-pending">Unfulfilled</span></td>
                            <td>5</td>
                            <td><span class="order-status status-pending">Pending</span></td>
                            <td>Standard</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td><a href="#">ORD-12341</a></td>
                            <td>Mar 15, 2025</td>
                            <td>Robert Brown</td>
                            <td><span class="badge bg-success">Mobile</span></td>
                            <td>₹1,800</td>
                            <td><span class="order-status status-delivered">Paid</span></td>
                            <td><span class="order-status status-delivered">Fulfilled</span></td>
                            <td>2</td>
                            <td><span class="order-status status-delivered">Delivered</span></td>
                            <td>Express</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="row mt-3">
        <div class="col-md-6">
            <p class="text-muted">Showing 1 to 5 of 237 entries</p>
        </div>
        <div class="col-md-6">
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-end">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Filter Sidebar -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterSidebar" aria-labelledby="filterSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filterSidebarLabel">
            <i class="fas fa-filter me-2"></i>Filter Orders
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form>
            <div class="mb-3">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Start Date">
                    <span class="input-group-text">to</span>
                    <input type="text" class="form-control" placeholder="End Date">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Order Status</label>
                <select class="form-select">
                    <option value="">Any</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Payment Status</label>
                <select class="form-select">
                    <option value="">Any</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Fulfillment Status</label>
                <select class="form-select">
                    <option value="">Any</option>
                    <option value="fulfilled">Fulfilled</option>
                    <option value="unfulfilled">Unfulfilled</option>
                    <option value="partial">Partial</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Channel</label>
                <select class="form-select">
                    <option value="">Any</option>
                    <option value="web">Web</option>
                    <option value="mobile">Mobile</option>
                    <option value="in-store">In-store</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Delivery Method</label>
                <select class="form-select">
                    <option value="">Any</option>
                    <option value="standard">Standard</option>
                    <option value="express">Express</option>
                    <option value="same-day">Same Day</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Total Amount</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Min">
                    <span class="input-group-text">to</span>
                    <input type="text" class="form-control" placeholder="Max">
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="button">Apply Filters</button>
                <button class="btn btn-outline-secondary" type="button">Clear All</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Initialize the filter sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const filterButton = document.getElementById('filterButton');
        const filterSidebar = new bootstrap.Offcanvas(document.getElementById('filterSidebar'));
        
        filterButton.addEventListener('click', function() {
            filterSidebar.show();
        });
        
        // Add hover effect to table rows
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Handle checkbox select all
        const selectAllCheckbox = document.querySelector('thead .form-check-input');
        const rowCheckboxes = document.querySelectorAll('tbody .form-check-input');
        
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    });
</script>