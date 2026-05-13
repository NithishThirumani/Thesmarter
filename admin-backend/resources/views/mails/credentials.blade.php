<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your theSmartr Login Credentials</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #000000;
            padding: 30px 20px;
            text-align: center;
        }
        .logo-img {
            max-width: 200px;
            height: auto;
        }
        .content {
            padding: 40px 35px;
        }
        .greeting {
            font-size: 20px;
            color: #000000;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .user-name {
            color: #FFD700;
        }
        .subtitle {
            font-size: 15px;
            color: #666666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .credentials-box {
            background: linear-gradient(135deg, #FFD700 0%, #FFC700 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            border: 3px solid #000000;
        }
        .credentials-title {
            font-size: 18px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 25px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .credential-row {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .credential-row:last-child {
            margin-bottom: 0;
        }
        .credential-label {
            font-size: 13px;
            color: #666666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 24px;
            color: #000000;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .pin-display {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .pin-digit {
            background-color: #f5f5f5;
            border: 2px solid #000000;
            border-radius: 6px;
            width: 45px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: #000000;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .security-notice-title {
            font-size: 15px;
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .security-notice-text {
            font-size: 14px;
            color: #856404;
            line-height: 1.6;
            margin: 0;
        }
        .login-steps {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            margin: 30px 0;
        }
        .steps-title {
            font-size: 18px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 20px;
            text-align: center;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .step-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .step-number {
            background-color: #FFD700;
            color: #000000;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
        .step-title {
            font-size: 15px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 5px;
        }
        .step-desc {
            font-size: 14px;
            color: #666666;
            line-height: 1.5;
        }
        .app-download-section {
            background-color: #000000;
            border-radius: 8px;
            padding: 35px;
            margin: 30px 0;
            text-align: center;
        }
        .app-download-title {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .app-download-subtitle {
            font-size: 14px;
            color: #cccccc;
            margin-bottom: 25px;
        }
        .app-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .app-button {
            display: inline-flex;
            align-items: center;
            background-color: #ffffff;
            color: #000000;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .app-button img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        .app-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .support-box {
            background-color: #f0f0f0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .support-title {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 10px;
        }
        .support-text {
            font-size: 14px;
            color: #555555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .support-contact {
            font-size: 15px;
            color: #000000;
            font-weight: 600;
        }
        .support-contact a {
            color: #FFD700;
            text-decoration: none;
        }
        .footer {
            background-color: #000000;
            color: #ffffff;
            padding: 30px;
            text-align: center;
            font-size: 12px;
            line-height: 1.8;
        }
        .footer-powered {
            color: #FFD700;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .footer-text {
            color: #888888;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-link {
            color: #FFD700;
            text-decoration: none;
            margin: 0 10px;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 25px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="https://ca-business.bizwy.in/v1/assets/thesmartr-logo.png" alt="theSmartr" class="logo-img">
        </div>

        <!-- Content -->
        <div class="content">
            <h1 class="greeting">Hello <span class="">{{ $name }}</span>,</h1>
            
            <p class="subtitle">
                Your theSmartr account has been successfully created! Below are your login credentials to access the platform. Please keep this information secure and confidential.
            </p>

            <!-- Credentials Box -->
            <div class="credentials-box">
                <div class="credentials-title">Your Login Credentials</div>
                
                <div class="credential-row">
                    <div>
                        <div class="credential-label">Phone Number</div>
                        <div class="credential-value">{{ $phone }}</div>
                    </div>
                </div>

                <div class="credential-row">
                    <div style="flex: 1;">
                        <div class="credential-label">4-Digit PIN</div>
                        <div class="pin-display">
                            <span class="pin-digit">{{ substr($pin, 0, 1) }}</span>
                            <span class="pin-digit">{{ substr($pin, 1, 1) }}</span>
                            <span class="pin-digit">{{ substr($pin, 2, 1) }}</span>
                            <span class="pin-digit">{{ substr($pin, 3, 1) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <div class="security-notice-title">
                    🔒 Security Important
                </div>
                <p class="security-notice-text">
                    <strong>Never share your PIN with anyone.</strong> theSmartr will never ask for your PIN via email, phone, or SMS. If you believe your credentials have been compromised, please change your PIN immediately from your account settings or contact our support team.
                </p>
            </div>

            <!-- Login Steps -->
            <div class="login-steps">
                <div class="steps-title">How to Login on Mobile App</div>

                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Download the App</div>
                        <div class="step-desc">Download theSmartr app from Google Play Store or Apple App Store using the links below.</div>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Open the App</div>
                        <div class="step-desc">Launch the theSmartr app on your mobile device and tap on "Login".</div>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Enter Your Phone Number</div>
                        <div class="step-desc">Enter your registered phone number: <strong>{{ $phone }}</strong></div>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <div class="step-title">Enter Your PIN</div>
                        <div class="step-desc">Enter your 4-digit PIN to complete the login process.</div>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <div class="step-title">Start Exploring</div>
                        <div class="step-desc">You're all set! Start browsing businesses, shopping, and booking services.</div>
                    </div>
                </div>
            </div>

            <!-- App Download Section -->
            

            <div class="divider"></div>

            <!-- Support Box -->
            <div class="support-box">
                <div class="support-title">Need Help?</div>
                <p class="support-text">
                    If you're having trouble logging in or have any questions about your account, our support team is here to help you 24/7.
                </p>
                <div class="">
                    Email: <a href="mailto:support@thesmartr.com">support@thesmartr.com</a><br>
                </div>
            </div>

            
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-powered">Powered by <strong>Bizwy</strong></div>
            <p class="footer-text">© 2024 theSmartr. All rights reserved.</p>
            <div class="footer-links">
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
                <a href="#" class="footer-link">Help Center</a>
            </div>
            <p class="footer-text" style="margin-top: 15px;">
                This email contains sensitive information. Please keep it confidential.
            </p>
        </div>
    </div>
</body>
</html>