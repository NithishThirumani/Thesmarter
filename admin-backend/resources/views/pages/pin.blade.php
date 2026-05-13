@extends('layouts.app')

@section('title', 'Verify Pin')

@section('content')
<style>
    
</style>


<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
        <div class="mb-4 text-center">
            <img src="https://bizwy.in/images/bizwy-logo.png" alt="Company Logo" class="img-fluid mb-3" style="max-width: 150px;">
        </div>
        <p class="text-muted text-center mb-4">Enter the 4-digit PIN for <strong id="phone-display"></strong></p>

        <form id="otp-form">
            <div class="otp-container">
                <!-- OTP input boxes -->
                <input type="text" id="otp-1" class="form-control otp-input text-center" maxlength="1" pattern="[0-9]" />
                <input type="text" id="otp-2" class="form-control otp-input text-center" maxlength="1" pattern="[0-9]" />
                <input type="text" id="otp-3" class="form-control otp-input text-center" maxlength="1" pattern="[0-9]" />
                <input type="text" id="otp-4" class="form-control otp-input text-center" maxlength="1" pattern="[0-9]" />
            </div>
            <small id="error-message" class="text-danger d-none"></small>

            <button id="verify-button" class="btn btn-primary w-100 mt-3" disabled>Verify</button>
        </form>
        <button id="back-button" class="btn btn-link text-decoration-none mt-3">Back to Login</button>
    </div>
</div>

<!-- Loader Mask -->
<div id="loader-mask" class="d-none position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light opacity-75" style="z-index: 1050;">
    <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-0">Processing...</p>
    </div>
</div>

<script>
    /*
    document.addEventListener("DOMContentLoaded", () => {
        const otpInputs = Array.from(document.querySelectorAll(".otp-input"));
        const verifyButton = document.querySelector("#verify-button");
        const errorMessage = document.querySelector("#error-message");
        const loader = document.querySelector("#loader-mask");
        const phoneDisplay = document.querySelector("#phone-display");
        const backButton = document.querySelector("#back-button");
console.log('contnetn loading');

        // Extract phone number from URL
        const urlParams = new URLSearchParams(window.location.search);

        const phoneNumber = urlParams.get("phone");

        if (phoneNumber) {
            phoneDisplay.textContent = phoneNumber;
        } else {
            phoneDisplay.textContent = "Unknown";
        }

        // Show loader
        const showLoader = () => loader.classList.remove("d-none");

        // Hide loader
        const hideLoader = () => loader.classList.add("d-none");

        // Navigate back to login
        backButton.addEventListener("click", () => {
            window.history.pushState({}, "", "/login");
            document.querySelector("#app").innerHTML = fetch("/login").then(res => res.text());
        });

        // Move focus on input and handle backspace
        otpInputs.forEach((input, index) => {
            input.addEventListener("input", (e) => {
                const value = e.target.value;
                if (/^[0-9]$/.test(value)) {
                    input.classList.remove("is-invalid");
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                } else {
                    e.target.value = ""; // Clear invalid input
                }
                updateButtonState();
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });

        // Enable or disable the verify button
        const updateButtonState = () => {
            const allFilled = otpInputs.every(input => input.value.length === 1);
            verifyButton.disabled = !allFilled;
        };

        // Submit OTP
        verifyButton.addEventListener("click", async (e) => {
            e.preventDefault();
            const otp = otpInputs.map(input => input.value).join("");
            showLoader();

            try {
                const response = await fetch("/api/v1/verify-pin", {
                    method: "POST"
                    , headers: {
                        "Content-Type": "application/json"
                    , }
                    , body: JSON.stringify({
                        phone: phoneNumber
                        , pin:otp
                    , })
                , });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Navigate to the user profile
                    window.history.pushState({}, "", "/profile");
                    document.querySelector("#app").innerHTML = await fetch("/profile").then(res => res.text());
                } else {
                    // Highlight inputs in red and display error
                    otpInputs.forEach(input => input.classList.add("is-invalid"));
                    errorMessage.textContent = data.message || "Invalid OTP. Please try again.";
                    errorMessage.classList.remove("d-none");
                }
            } catch (err) {
                errorMessage.textContent = "Failed to connect to the server. Please try again.";
                errorMessage.classList.remove("d-none");
            } finally {
                hideLoader();
            }
        });
    });
*/

</script>
@endsection
