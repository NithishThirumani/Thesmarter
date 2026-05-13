@extends('layouts.app')

@section('title', 'Login')

@section('content')
<style>
   
</style>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
        <div class="mb-4 text-center">
            <img src="https://bizwy.in/images/bizwy-logo.png" alt="Company Logo" class="img-fluid mb-3" style="max-width: 150px;">
        </div>
        <div>
            <p class="text-muted mb-3">Enter your registered phone number to login</p>
        </div>
        <form id="login-form">
            <!-- Hidden fields to separate country code and phone number -->
            <input type="hidden" id="country-code" />
            <input type="hidden" id="phone-number" />
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" class="form-control" placeholder="Enter your phone number" style="padding-left: 50px;">
            </div>
            <small id="error-message" class="text-danger d-none"></small>
            <button id="login-button" class="btn btn-primary w-100" disabled>Next</button>
        </form>
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

{{-- <script>
    document.addEventListener("DOMContentLoaded", () => {
        const phoneInput = document.querySelector("#phone");
        const loginButton = document.querySelector("#login-button");
        const errorMessage = document.querySelector("#error-message");
        const countryCodeField = document.getElementById("country-code");
        const phoneNumberField = document.getElementById("phone-number");
        const loader = document.getElementById("loader-mask");

        // Show loader
        const showLoader = () => {
            loader.classList.remove("d-none");
        };

        // Hide loader
        const hideLoader = () => {
            loader.classList.add("d-none");
        };

        // Initialize intl-tel-input
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "in", // Default to India
            separateDialCode: true, // Show the country code separately
            utilsScript: "{{ asset('js/utils.js') }}", // Load utils.js for validation
});

// Enable button after 10 digits and a valid phone number
phoneInput.addEventListener("input", () => {
const countryCode = iti.getSelectedCountryData().dialCode; // Get the dial code
const fullPhoneNumber = iti.getNumber();
const localNumber = phoneInput.value.replace(/\D/g, ""); // Only digits
const isLocalValid = localNumber.length === 10; // Ensure local length is 10

// Update the separate fields
countryCodeField.value = `+${countryCode}`;
phoneNumberField.value = fullPhoneNumber.replace(`+${countryCode}`, "").trim();

// Enable button if valid
loginButton.disabled = !isLocalValid;
});

// Handle form submission
loginButton.addEventListener("click", async (e) => {
e.preventDefault();

const fullPhoneNumber = iti.getNumber(); // Get E.164 formatted phone number
/*if (!iti.isValidNumber()) {
errorMessage.textContent = "Please enter a valid phone number.";
errorMessage.classList.remove("d-none");
return;
}*/
const phoneNumber = phoneNumberField.value;
const countryCode = countryCodeField.value;
showLoader(); // Show loader before the API call

// Make API call
try {
const response = await fetch("/api/v1/verify-phone", {
method: "POST",
headers: {
"Content-Type": "application/json",
},
body: JSON.stringify({
phone: phoneNumber,
country_code: countryCode,
}),
});

const data = await response.json();

if (response.ok && data.success) {
// Navigate to OTP screen
window.history.pushState({}, '', `/otp?phone=${encodeURIComponent(fullPhoneNumber)}`);
document.querySelector('#app').innerHTML = await fetch('/otp').then((res) => res.text());
} else {
errorMessage.textContent = data.message || "Phone number validation failed.";
errorMessage.classList.remove("d-none");
}
} catch (err) {
errorMessage.textContent = "Failed to connect to the server. Please try again.";
errorMessage.classList.remove("d-none");
} finally {
hideLoader(); // Hide loader after the API call
}
});

// Handle Browser Back Button
window.onpopstate = async () => {
const currentPath = window.location.pathname;
const pageHtml = await fetch(currentPath).then((res) => res.text());
document.querySelector('#app').innerHTML = pageHtml;
};
});
</script> --}}
@endsection
