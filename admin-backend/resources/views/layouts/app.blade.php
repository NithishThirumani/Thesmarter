<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/css/intlTelInput.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(140deg, #f8e21c, #3971c6);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .iti {
            width: 100%;
        }

        #login-button {
            transition: 0.3s;
        }

        #login-button:disabled {
            background: #ccc;
            border: none;
        }

        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .otp-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
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
    <div class="container">
        <div id="app">
            @yield('content')
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/js/intlTelInput.min.js"></script>
    <script>
        const input = document.querySelector("#phone");
        if (input) {
            window.intlTelInput(input, {
                loadUtils: () => import("https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/js/utils.js")
            , });
        }

    </script>
   
    <script>
        // Utilities for Loader
        const showLoader = () => {
            const loader = document.querySelector("#loader-mask");
            if (loader) loader.classList.remove("d-none");
        };

        const hideLoader = () => {
            const loader = document.querySelector("#loader-mask");
            if (loader) loader.classList.add("d-none");
        };

        // Authenticate and Protect Routes
        const checkAuth = () => {
            const authToken = localStorage.getItem("authToken");
            if (!authToken) {
                redirectToLogin();
                return false;
            }
            return true;
        };

        const redirectToLogin = () => {
            window.location.href = "/login";
        };

        // API Utility with Authentication Header
        const authenticatedFetch = async (url, options = {}) => {
            {{-- const authToken = localStorage.getItem("authToken");
            if (!authToken) {
                redirectToLogin();
                return;
            }

            options.headers = {
                ...options.headers
                , Authorization: `Bearer ${authToken}`
            , }; --}}

            const response = await fetch(url, options);

            // Redirect to login if unauthorized
            if (response.status === 401) {
                redirectToLogin();
            }

            return response;
        };

        // Load Page Dynamically
        const loadPage = async (url, containerId = "#app", additionalData = {}) => {
           
            try {
                const response = await authenticatedFetch(url);
                
                if (!response.ok) throw new Error("Page load failed.");
                const html = await response.text();
                document.querySelector(containerId).innerHTML = html;

                initPageLogic(url, additionalData); // Reinitialize JS logic for the new page
            } catch (error) {
                console.error("Error loading page:", error);
            }
        };

        // Login Page Logic
        const handleLoginPage = () => {
            const phoneInput = document.querySelector("#phone");
            const loginButton = document.querySelector("#login-button");
            const errorMessage = document.querySelector("#error-message");
            const countryCodeField = document.getElementById("country-code");
            const phoneNumberField = document.getElementById("phone-number");


            const iti = window.intlTelInput(phoneInput, {
                initialCountry: "in"
                , separateDialCode: true
                , utilsScript: "/assets/js/utils.js"
            , });

            phoneInput.addEventListener("input", () => {
                const fullPhoneNumber = iti.getNumber();
                const isValid = iti.isValidNumber();
                const localNumber = phoneInput.value.replace(/\D/g, "");
                const isLocalValid = localNumber.length === 10;

                countryCodeField.value = `+${iti.getSelectedCountryData().dialCode}`;
                phoneNumberField.value = fullPhoneNumber.replace(`+${iti.getSelectedCountryData().dialCode}`, "").trim();

                loginButton.disabled = !isLocalValid;
            });
            phoneInput.focus();

            loginButton.addEventListener("click", async (e) => {
                e.preventDefault();
                const fullPhoneNumber = iti.getNumber();

                if (!iti.isValidNumber()) {
                    errorMessage.textContent = "Please enter a valid phone number.";
                    errorMessage.classList.remove("d-none");
                    return;
                }

                const phoneNumber = phoneNumberField.value;

                showLoader();
                try {
                    const response = await fetch("/api/v1/verify-phone", {
                        method: "POST"
                        , headers: {
                            "Content-Type": "application/json"
                        , }
                        , body: JSON.stringify({
                            phone: phoneNumber
                        , })
                    , });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        // localStorage.setItem("authToken", data.token); // Store authentication token
                        {{-- window.history.pushState({}, "", `/otp?phone=${encodeURIComponent(phoneNumber)}`); --}}
                        loadPage("/otp", "#app", {
                            phone: phoneNumber
                        });
                    } else {
                        errorMessage.textContent = data.message || "Phone number validation failed.";
                        errorMessage.classList.remove("d-none");
                    }
                } catch (error) {
                    console.log(error.message);
                    errorMessage.textContent = "Failed to connect to the server. Please try again.";
                    errorMessage.classList.remove("d-none");
                } finally {
                    hideLoader();
                }
            });
        };

        // OTP Page Logic
        const handleOtpPage = (data) => {
            const otpInputs = Array.from(document.querySelectorAll(".otp-input"));
            const verifyButton = document.querySelector("#verify-button");
            const errorMessage = document.querySelector("#error-message");
            const phoneDisplay = document.querySelector("#phone-display");
            const backButton = document.querySelector("#back-button");

            if (data && data.phone) {
                phoneDisplay.textContent = data.phone;
            }

            otpInputs.forEach((input, index) => {
                input.addEventListener("input", (e) => {
                    if (/^[0-9]$/.test(e.target.value)) {
                        input.classList.remove("is-invalid");
                        if (index < otpInputs.length - 1) otpInputs[index + 1].focus();
                    } else {
                        e.target.value = "";
                    }
                    updateOtpButtonState();
                });

                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && !input.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });

            const updateOtpButtonState = () => {
                const allFilled = otpInputs.every((input) => input.value.length === 1);
                verifyButton.disabled = !allFilled;
            };
            backButton.addEventListener("click", () => {
                window.history.pushState({}, "", "/login");
                loadPage("/login");
            });
            verifyButton.addEventListener("click", async (e) => {
                e.preventDefault();
                const pin = otpInputs.map((input) => input.value).join("");

                showLoader();
                try {
                    const response = await fetch("/api/v1/verify-pin", {
                        method: "POST"
                        , headers: {
                            "Content-Type": "application/json"
                        , }
                        
                        , body: JSON.stringify({
                            phone: data.phone
                            , pin
                        , }),
                        credentials: "include" // Ensures cookies (auth token) are sent with requests
                     });

                    const resData = await response.json();
                    if (response.ok && resData.success) {
                        window.location.href = '/dashboard/';

                        {{-- console.log('Now we will redirect'); --}}
                        
                    } else {
                        otpInputs.forEach((input) => input.classList.add("is-invalid"));
                        errorMessage.textContent = resData.message || "Invalid OTP. Please try again.";
                        errorMessage.classList.remove("d-none");
                    }
                } catch (error) {
                    console.log(error.message);
                    errorMessage.textContent = "Failed to connect to the server. Please try again.";
                    errorMessage.classList.remove("d-none");
                } finally {
                    hideLoader();
                }
            });
        };

        // Dashboard Page Logic
        const handleDashboardPage = () => {
            console.log("Dashboard logic initialized.");
        };

        // Initialize SPA
        const initPageLogic = (url, additionalData = {}) => {
            if (url.includes("/login")) {
                handleLoginPage();
            } else if (url.includes("/otp")) {
                handleOtpPage(additionalData);
            } else if (url.includes("/dashboard")) {
                if (checkAuth()) handleDashboardPage();
            }
        };

        document.addEventListener("DOMContentLoaded", () => {
            const currentPath = window.location.pathname;
            initPageLogic(currentPath);
        });

    </script>


</body>
</html>
