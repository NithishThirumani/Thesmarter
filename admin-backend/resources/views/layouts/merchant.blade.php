<!DOCTYPE html>
<html lang="en">.
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Portal</title>
    <meta name="Description" content="" />
	<meta name="Keywords" content="" />
	<meta property="og:image" content="https://www.bizwy.in/images/tp_logo_436.png">
	<meta property="og:image:type" content="image/png">
	<meta property="og:image:width" content="436">
	<meta property="og:image:height" content="228">
	<meta property="og:description" content="">
    <link rel="icon" href="https://www.bizwy.in/images/favicon.ico?1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-bg: #f8f9fa;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #1a237e;
            color: #fff;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            transition: var(--transition);
            border-radius: 8px;
            margin: 0.2rem 1rem;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }



        .user-profile {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .user-initials {
            width: 80px;
            height: 80px;
            line-height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            font-weight: 500;
        }

        .company-logo {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            text-align: center;
            padding: 0 1rem;
        }

        .company-logo img {
            max-width: 180px;
            opacity: 0.8;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .hidden {
            display: none;
        }

        .sidebar_mobile {
            background: #fff;
            margin: -31px 0px 0px -34px;
            position: absolute;
            width: 100%;
            padding: 4px 0px 0px 20px;
            font-size: 20px;
        }

        @media(max-width:768px) {
            .sidebar_mobile {
                display: block;
            }

            .main-content {
                margin-left: 0px !important;
            }
        }

        @media(min-width:768px) {
            .sidebar_mobile {
                display: none;
            }

            #toggleContent {
                display: block !important;
            }
        }

        /* Loader masking */
        #loader-mask {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #loader-mask .loading-text {
            font-size: 1.5rem;
            margin-top: 10px;
        }

        #loader-mask .loader-bar {
            width: 100%;
            height: 4px;
            background: yellow;
            animation: loader-anim 2s infinite;
        }

        @keyframes loader-anim {
            0% {
                transform: translateX(-100%);
            }

            50% {
                transform: translateX(50%);
            }

            100% {
                transform: translateX(100%);
            }
        }

    </style>
</head>

<body>
    <div id="loader-mask" class="d-none">
        <div>
            <div class="loader-bar"></div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>
    <div class="">
        <!-- <div class="d-flex">
         Sidebar -->
        <nav class="sidebar hidden" id="toggleContent">
            <div class="user-profile">
                <div id="user-avatar" class="user-initials"></div>
                <h6 id="user-name" class="mb-0"></h6>
                <small id="company-name" class="text-light-50"></small>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="#dashboard" class="nav-link active" data-content="dashboard">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#profile" class="nav-link" data-content="profile">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#orders" class="nav-link" data-content="orders">
                        <i class="fas fa-shopping-bag me-2"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#security" class="nav-link" data-content="security">
                        <i class="fas fa-shield-alt me-2"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#change-pin" class="nav-link" data-content="change-pin">
                        <i class="fas fa-cog me-2"></i> Change Pin
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#logout" class="nav-link" data-content="logout">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>

            <div class="company-logo">
                <img src="https://bizwy.in/images/bizwy-logo.png" alt="Company Logo">
            </div>
        </nav>

        <!-- Content -->
        <main class="main-content" id="main-content">
            <div class="sidebar_mobile" id="toggleButton"><i class="fa fa-bars mobile-bars"></i></div>
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", async function() {
            const mainContent = document.getElementById("main-content");
            const userAvatar = document.getElementById("user-avatar");
            const userName = document.getElementById("user-name");
            const links = document.querySelectorAll(".nav-link");

            let userData = null; // Declare userData globally

            // Utilities for Loader
            const showLoader = () => {
                const loader = document.querySelector("#loader-mask");
                if (loader) loader.classList.remove("d-none");
            };

            const hideLoader = () => {
                const loader = document.querySelector("#loader-mask");
                if (loader) loader.classList.add("d-none");
            };

            showLoader();
            try {
                const response = await fetch("/api/v1/user", {
                    headers: {
                        "Content-Type": "application/json"
                    }
                    , credentials: "include"
                });
                if (!response.ok) {
                    throw new Error("Unauthorized");
                }
                userData = await response.json(); // Populate userData
                document.querySelector("#user-name").textContent = userData.first_name + ' ' + userData.last_name;
                document.querySelector("#company-name").textContent = userData.user_companies[0].company.company_name;
            } catch (error) {
                console.log("Authentication failed:" + error.message);
                window.location.href = "/login"; // Redirect to login if unauthorized
            } finally {
                hideLoader();
            }

            // Display User Profile Image or Initials
            const displayUserAvatar = () => {
                if (userData.image) {
                    userAvatar.innerHTML = `<img src="${userData.image}" alt="User Avatar" class="rounded-circle" width="80" height="80">`;
                } else {
                    const initials = userData.first_name[0] + userData.last_name[0];
                    userAvatar.innerHTML = `<div class="user-initials rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                  style="width: 80px; height: 80px; font-size: 24px;">${initials}</div>`;
                }
                userName.textContent = userData.first_name + ' ' + userData.last_name;
            };

            displayUserAvatar();

            // Handle Dynamic Content Loading
            const handleSideBar = () => {
                links.forEach(link => {
                    link.addEventListener("click", async (e) => {
                        e.preventDefault();
                        const content = link.getAttribute("data-content");
                        showLoader();
                        // Update Active Link
                        links.forEach(l => l.classList.remove("active"));
                        link.classList.add("active");

                        // Fetch and Render New Content
                        try {
                            const response = await fetch(`/${content}-content`);
                            const data = await response.text();
                            mainContent.innerHTML = data;
                            window.history.pushState({}, "", `/${content}`);
                            initPageLogic("/" + content);
                        } catch (err) {
                            console.error("Error loading content:", err);
                        } finally {
                            hideLoader();
                        }
                    });
                });
            };

            // Handle Dashboard Logic
            const handleDashboardPage = () => {
                // Add hover effect to tasks
                document.querySelectorAll(".list-group-item").forEach(item => {
                    item.addEventListener("mouseenter", function() {
                        this.style.backgroundColor = "#f8f9fa";
                    });
                    item.addEventListener("mouseleave", function() {
                        this.style.backgroundColor = "";
                    });
                });

                // Add functionality to checkboxes
                document.querySelectorAll(".form-check-input").forEach(checkbox => {
                    checkbox.addEventListener("change", function() {
                        const listItem = this.closest(".list-group-item");
                        if (this.checked) {
                            listItem.style.textDecoration = "line-through";
                            listItem.style.opacity = "0.5";
                        } else {
                            listItem.style.textDecoration = "none";
                            listItem.style.opacity = "1";
                        }
                    });
                });
            };

            // Handle Profile Logic
            const handleProfilePage = () => {
                // Populate user data
                let sex = 'N/A';
                let marital_status = 'Single';
                if (userData.user_gender == 'M') {
                    sex = 'Male';
                } else if (userData.user_gender == 'F') {
                    sex = 'FeMale';
                } else if (userData.user_gender = 'O') {
                    sex = 'Others'
                }
                if (userData.marital_status == 'M') {
                    sex = 'Married';
                }

                document.getElementById('userName').textContent = userData.first_name + ' ' + userData.last_name;
                document.getElementById('userDob').textContent = userData.user_dob || 'N/A';
                document.getElementById('userSex').textContent = sex;
                document.getElementById('userMarital').textContent = marital_status;
                document.getElementById('userPhone').textContent = userData.phone || 'N/A';
                document.getElementById('userEmail').textContent = userData.email || 'N/A';
                document.getElementById('userCompany').textContent = userData.user_companies[0].company.company_name || 'N/A';

                // Set avatar
                const avatarText = userData.first_name[0] + userData.last_name[0];
                document.getElementById('userAvatar').textContent = avatarText;
            };

            // Handle Security Logic
            const handleSecurityPage = () => {
                // Using ES6 syntax
                const pinModal = new bootstrap.Modal(document.getElementById('pinModal'));
                const deleteAccountBtn = document.getElementById('deleteAccountBtn');
                const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                const pinInputs = document.querySelectorAll('.pin-input-box');
                const invalidFeedback = document.querySelector('.invalid-feedback');

                // Mock PIN for demonstration
                const CORRECT_PIN = '1234';

                // Event Listeners
                deleteAccountBtn.addEventListener('click', () => {
                    pinInputs.forEach(input => {
                        input.value = '';
                        input.classList.remove('is-invalid');
                    });
                    invalidFeedback.classList.add('d-none');
                    pinModal.show();
                    pinInputs[0].focus();
                });

                pinInputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => {
                        e.target.value = e.target.value.replace(/[^0-9]/g, '');

                        if (e.target.value !== '') {
                            if (index < pinInputs.length - 1) {
                                pinInputs[index + 1].focus();
                            }
                        }

                        pinInputs.forEach(input => input.classList.remove('is-invalid'));
                        invalidFeedback.classList.add('d-none');
                    });

                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && e.target.value === '') {
                            if (index > 0) {
                                pinInputs[index - 1].focus();
                            }
                        }
                    });
                });

                confirmDeleteBtn.addEventListener('click', () => {
                    const enteredPin = Array.from(pinInputs)
                        .map(input => input.value)
                        .join('');

                    if (enteredPin === CORRECT_PIN) {
                        pinModal.hide();
                        showSuccessMessage().then(() => {
                            window.location.href = '/login.html';
                        });
                    } else {
                        pinInputs.forEach(input => input.classList.add('is-invalid'));
                        invalidFeedback.classList.remove('d-none');
                    }
                });

                const showSuccessMessage = () => {
                    return new Promise((resolve) => {
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success position-fixed top-50 start-50 translate-middle';
                        successAlert.innerHTML = 'Account successfully deleted. Redirecting...';
                        document.body.appendChild(successAlert);

                        setTimeout(() => {
                            successAlert.remove();
                            resolve();
                        }, 2000);
                    });
                };
            };

            // Handle Change PIN Logic
            const handleChangePinPage = () => {
                // Mock current PIN for demonstration
                const CURRENT_PIN = '1234';

                let currentStep = 1;
                let newPin = '';

                const nextBtn = document.getElementById('nextBtn');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorMessage = document.getElementById('errorMessage');

                // Initialize all PIN input groups
                document.querySelectorAll('.pin-input-group').forEach(group => {
                    const inputs = group.querySelectorAll('.pin-input-box');

                    inputs.forEach((input, index) => {
                        // Handle input
                        input.addEventListener('input', (e) => {
                            e.target.value = e.target.value.replace(/[^0-9]/g, '');

                            if (e.target.value !== '') {
                                if (index < inputs.length - 1) {
                                    inputs[index + 1].focus();
                                }
                            }
                        });

                        // Handle backspace
                        input.addEventListener('keydown', (e) => {
                            if (e.key === 'Backspace' && e.target.value === '') {
                                if (index > 0) {
                                    inputs[index - 1].focus();
                                }
                            }
                        });
                    });
                });

                function showError(message) {
                    errorMessage.textContent = message;
                    errorMessage.classList.remove('d-none');
                }

                function hideError() {
                    errorMessage.classList.add('d-none');
                }

                function getStepPIN(stepNumber) {
                    const inputs = document.querySelector(`#step${stepNumber} .pin-input-group`)
                        .querySelectorAll('.pin-input-box');
                    return Array.from(inputs).map(input => input.value).join('');
                }

                function clearStepInputs(stepNumber) {
                    const inputs = document.querySelector(`#step${stepNumber} .pin-input-group`)
                        .querySelectorAll('.pin-input-box');
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }

                function updateStepIndicator() {
                    document.querySelectorAll('.step').forEach((step, index) => {
                        step.classList.remove('active', 'completed');
                        if (index + 1 === currentStep) {
                            step.classList.add('active');
                        } else if (index + 1 < currentStep) {
                            step.classList.add('completed');
                        }
                    });
                }

                function showStep(step) {
                    document.querySelectorAll('.step-content').forEach(content => {
                        content.classList.add('d-none');
                    });
                    document.getElementById(`step${step}`).classList.remove('d-none');

                    // Focus first input of new step
                    const firstInput = document.querySelector(`#step${step} .pin-input-box`);
                    if (firstInput) firstInput.focus();

                    // Update button text
                    nextBtn.textContent = step === 3 ? 'Change PIN' : 'Next';
                }

                async function handleNextStep() {
                    hideError();
                    const currentPIN = getStepPIN(currentStep);

                    // Validate complete PIN entry
                    if (currentPIN.length !== 4) {
                        showError('Please enter all 4 digits');
                        return;
                    }

                    switch (currentStep) {
                        case 1:
                            // Verify current PIN
                            if (currentPIN !== CURRENT_PIN) {
                                showError('Current PIN is incorrect');
                                clearStepInputs(1);
                                return;
                            }
                            break;

                        case 2:
                            // Store new PIN for confirmation
                            newPin = currentPIN;
                            break;

                        case 3:
                            // Confirm new PIN matches
                            if (currentPIN !== newPin) {
                                showError('PINs do not match');
                                clearStepInputs(3);
                                return;
                            }

                            // Submit PIN change
                            try {
                                loadingOverlay.style.display = 'flex';

                                // Simulate API call
                                await new Promise(resolve => setTimeout(resolve, 1500));

                                // Redirect on success
                                window.location.href = '/security-settings.html?pinChanged=true';
                            } catch (error) {
                                loadingOverlay.style.display = 'none';
                                showError('An error occurred. Please try again.');
                            }
                            return;
                    }

                    // Move to next step
                    currentStep++;
                    updateStepIndicator();
                    showStep(currentStep);
                }

                nextBtn.addEventListener('click', handleNextStep);

                // Initialize first step
                updateStepIndicator();
                showStep(1);
            };

            // Handle Logout
            const handleLogoutPage = async () => {
                try {
                    // Call the logout API to invalidate the token
                    const response = await fetch("/api/v1/logout", {
                        method: "POST"
                        , credentials: "include"
                    });

                    if (response.ok) {
                        // Clear the api_token cookie
                        document.cookie = "api_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.bizwy.in;";

                        // Redirect to login page
                        window.location.href = "/login";
                    } else {
                        console.error("Logout failed");
                    }
                } catch (error) {
                    console.error("Error during logout:", error);
                }
            };
            // Add this new function to handle orders page logic
const handleOrdersPage = () => {
    // Initialize the filter sidebar
    const filterButton = document.getElementById('filterButton');
    if (filterButton) {
        const filterSidebar = new bootstrap.Offcanvas(document.getElementById('filterSidebar'));
        
        filterButton.addEventListener('click', function() {
            filterSidebar.show();
        });
    }
    
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
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
};


            // Initialize Page Logic Based on URL
            const initPageLogic = (url) => {
                if (url.includes("/dashboard")) {
                    handleDashboardPage();
                } else if (url.includes("/profile")) {
                    handleProfilePage();
                } else if (url.includes("/security")) {
                    handleSecurityPage();
                } else if (url.includes("/change-pin")) {
                    handleChangePinPage();
                } else if (url.includes("/logout")) {
                    handleLogoutPage();
                } else if (url.includes("/orders")) {
                    handleOrdersPage();
                }
            };

            // Initialize SPA
            const currentPath = window.location.pathname;
            initPageLogic(currentPath);
            handleSideBar();

            // Handle Back/Forward Navigation
            window.addEventListener("popstate", () => {
                const path = window.location.pathname;
                initPageLogic(path);
            });
        });

    </script>

    <script>
        // Select the toggle button and the content to show/hide
        const toggleButton = document.getElementById("toggleButton");
        const toggleContent = document.getElementById("toggleContent");

        // Add a click event listener to the button
        toggleButton.addEventListener("click", () => {
            // Toggle the 'hidden' class on the content
            toggleContent.classList.toggle("hidden");

            // Update button text based on visibility
            if (toggleContent.classList.contains("hidden")) {
                toggleButton.textContent = "show";
            } else {
                toggleButton.textContent = "Hide";
            }
        });

        if (window.innerWidth <= 768) { // Check if viewport width is 768px or less
            toggleButton.style.display = "block";
        } else {
            toggleContent.classList.remove("hidden"); // Always show content on larger screens
            toggleButton.style.display = "none"; // Hide toggle button on larger screens
        }

    </script>
</body>

</html>
