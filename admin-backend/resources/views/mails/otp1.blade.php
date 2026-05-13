<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>theSmartr - Verify Your Email</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #000000;
            padding: 0px 0px 0px 50px
            {{-- text-align: center; --}}
        }
        .logo-img {
            max-width: 200px;
            height: auto;
        }
        .powered-by {
            color: #ffffff;
            font-size: 12px;
            margin-top: 5px;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #000000;
        }
        .message {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666666;
        }
        .otp-container {
            background-color: #FFD700;
            border: 3px solid #000000;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-label {
            font-size: 14px;
            color: #000000;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 42px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 12px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .otp-digit {
            background-color: #ffffff;
            border: 2px solid #000000;
            border-radius: 8px;
            width: 50px;
            height: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        .validity {
            font-size: 12px;
            color: #000000;
            margin-top: 10px;
        }
        .warning {
            background-color: #fffbea;
            border-left: 4px solid #FFD700;
            padding: 15px;
            margin: 20px 0;
            font-size: 13px;
            color: #666666;
        }
        .footer {
            background-color: #000000;
            color: #ffffff;
            padding: 20px 30px;
            font-size: 12px;
            text-align: center;
            line-height: 1.6;
        }
        .footer a {
            color: #FFD700;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
    </style> 
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="https://ca-business.bizwy.in/v1/assets/thesmartr-logo.png" alt="theSmartr" class="logo-img">
            {{-- <p class="powered-by">Powered by Bizwy</p> --}}
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="greeting">Hello!</h2>
            
            <p class="message">
                Thank you for registering with <strong>theSmartr</strong>. To complete your registration and verify your email address, please use the One-Time Password (OTP) below.
            </p>

            <!-- OTP Box -->
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">
                    <span class="otp-digit">{{ substr($otp, 0, 1) }}</span>
                    <span class="otp-digit">{{ substr($otp, 1, 1) }}</span>
                    <span class="otp-digit">{{ substr($otp, 2, 1) }}</span>
                    <span class="otp-digit">{{ substr($otp, 3, 1) }}</span>
                    <span class="otp-digit">{{ substr($otp, 4, 1) }}</span>
                    <span class="otp-digit">{{ substr($otp, 5, 1) }}</span>
                </div>
                <div class="validity">Valid for 10 minutes</div>
            </div>

            <p class="message">
                Enter this code on the registration page to verify your email and activate your account.
            </p>

            <!-- Warning Box -->
            <div class="warning">
                <strong>Security Note:</strong> Never share this OTP with anyone. theSmartr will never ask you for this code via phone or email. If you didn't request this verification, please ignore this email.
            </div>

            <div class="divider"></div>

            <p class="message" style="font-size: 13px;">
                If you have any questions or need assistance, feel free to reach out to our support team.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2024 theSmartr. All rights reserved.</p>
            <p>Powered by <strong style="color: #FFD700;">Bizwy</strong></p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>